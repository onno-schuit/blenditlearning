<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Return token
 * @package    moodlecore
 * @copyright  2011 Dongsheng Cai <dongsheng@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
define('NO_MOODLE_COOKIES', true);

require_once(dirname(dirname(__FILE__)) . '/config.php');

$username = required_param('username', PARAM_USERNAME);
$password = required_param('password', PARAM_RAW);
$serviceshortname  = required_param('service',  PARAM_ALPHANUMEXT);

echo $OUTPUT->header();

if (!$CFG->enablewebservices) {
    throw new moodle_exception('enablewsdescription', 'webservice');
}
$username = trim(moodle_strtolower($username));
if (is_restored_user($username)) {
    throw new moodle_exception('restoredaccountresetpassword', 'webservice');
}
$user = authenticate_user_login($username, $password);
if (!empty($user)) {
    if (isguestuser($user)) {
        throw new moodle_exception('noguest');
    }
    if (empty($user->confirmed)) {
        throw new moodle_exception('usernotconfirmed', 'moodle', '', $user->username);
    }
    // check credential expiry
    $userauth = get_auth_plugin($user->auth);
    if (!empty($userauth->config->expiration) and $userauth->config->expiration == 1) {
        $days2expire = $userauth->password_expire($user->username);
        if (intval($days2expire) < 0 ) {
            throw new moodle_exception('passwordisexpired', 'webservice');
        }
    }

    // setup user session to check capability
    session_set_user($user);

    //check if the service exists and is enabled
    $service = $DB->get_record('external_services', array('shortname' => $serviceshortname, 'enabled' => 1));
    if (empty($service)) {
        // will throw exception if no token found
        throw new moodle_exception('servicenotavailable', 'webservice');
    }

    //check if there is any required system capability
    if ($service->requiredcapability and !has_capability($service->requiredcapability, get_context_instance(CONTEXT_SYSTEM), $user)) {
        throw new moodle_exception('missingrequiredcapability', 'webservice', '', $service->requiredcapability);
    }

    //specific checks related to user restricted service
    if ($service->restrictedusers) {
        $authoriseduser = $DB->get_record('external_services_users',
            array('externalserviceid' => $service->id, 'userid' => $user->id));

        if (empty($authoriseduser)) {
            throw new moodle_exception('usernotallowed', 'webservice', '', $serviceshortname);
        }

        if (!empty($authoriseduser->validuntil) and $authoriseduser->validuntil < time()) {
            throw new moodle_exception('invalidtimedtoken', 'webservice');
        }

        if (!empty($authoriseduser->iprestriction) and !address_in_subnet(getremoteaddr(), $authoriseduser->iprestriction)) {
            throw new moodle_exception('invalidiptoken', 'webservice');
        }
    }

    //Check if a token has already been created for this user and this service
    //Note: this could be an admin created or an user created token.
    //      It does not really matter we take the first one that is valid.
    $tokenssql = "SELECT t.id, t.sid, t.token, t.validuntil, t.iprestriction
              FROM {external_tokens} t
             WHERE t.userid = ? AND t.externalserviceid = ? AND t.tokentype = ?
          ORDER BY t.timecreated ASC";
    $tokens = $DB->get_records_sql($tokenssql, array($user->id, $service->id, EXTERNAL_TOKEN_PERMANENT));

    //A bit of sanity checks
    foreach ($tokens as $key=>$token) {

        /// Checks related to a specific token. (script execution continue)
        $unsettoken = false;
        //if sid is set then there must be a valid associated session no matter the token type
        if (!empty($token->sid)) {
            $session = session_get_instance();
            if (!$session->session_exists($token->sid)){
                //this token will never be valid anymore, delete it
                $DB->delete_records('external_tokens', array('sid'=>$token->sid));
                $unsettoken = true;
            }
        }

        //remove token if no valid anymore
        //Also delete this wrong token (similar logic to the web service servers
        //    /webservice/lib.php/webservice_server::authenticate_by_token())
        if (!empty($token->validuntil) and $token->validuntil < time()) {
            $DB->delete_records('external_tokens', array('token'=>$token->token, 'tokentype'=> EXTERNAL_TOKEN_PERMANENT));
            $unsettoken = true;
        }

        // remove token if its ip not in whitelist
        if (isset($token->iprestriction) and !address_in_subnet(getremoteaddr(), $token->iprestriction)) {
            $unsettoken = true;
        }

        if ($unsettoken) {
            unset($tokens[$key]);
        }
    }

    // if some valid tokens exist then use the most recent
    if (count($tokens) > 0) {
        $token = array_pop($tokens);
    } else {
        if ( ($serviceshortname == MOODLE_OFFICIAL_MOBILE_SERVICE and has_capability('moodle/webservice:createmobiletoken', get_system_context()))
                //Note: automatically token generation is not available to admin (they must create a token manually)
                or (!is_siteadmin($user) && has_capability('moodle/webservice:createtoken', get_system_context()))) {
            // if service doesn't exist, dml will throw exception
            $service_record = $DB->get_record('external_services', array('shortname'=>$serviceshortname, 'enabled'=>1), '*', MUST_EXIST);
            // create a new token
            $token = new stdClass;
            $token->token = md5(uniqid(rand(), 1));
            $token->userid = $user->id;
            $token->tokentype = EXTERNAL_TOKEN_PERMANENT;
            $token->contextid = get_context_instance(CONTEXT_SYSTEM)->id;
            $token->creatorid = $user->id;
            $token->timecreated = time();
            $token->externalserviceid = $service_record->id;
            $tokenid = $DB->insert_record('external_tokens', $token);
            add_to_log(SITEID, 'webservice', get_string('createtokenforuserauto', 'webservice'), '' , 'User ID: ' . $user->id);
            $token->id = $tokenid;
        } else {
            throw new moodle_exception('cannotcreatetoken', 'webservice', '', $serviceshortname);
        }
    }

    // log token access
    $DB->set_field('external_tokens', 'lastaccess', time(), array('id'=>$token->id));

    add_to_log(SITEID, 'webservice', 'user request webservice token', '' , 'User ID: ' . $user->id);

    $usertoken = new stdClass;
    $usertoken->token = $token->token;
    echo json_encode($usertoken);
} else {
    throw new moodle_exception('usernamenotfound', 'moodle');
}
