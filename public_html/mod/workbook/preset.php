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
 * Preset Menu
 *
 * This is the page that is the menu item in the config workbook
 * pages.
 *
 * This file is part of the Workbook module for Moodle
 *
 * @copyright 2005 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod-workbook
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/workbook/lib.php');
require_once($CFG->dirroot.'/mod/workbook/preset_form.php');
require_once($CFG->libdir.'/xmlize.php');

$id     = optional_param('id', 0, PARAM_INT);           // course module id
if ($id) {
    $cm = get_coursemodule_from_id('workbook', $id, null, null, MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $workbook = $DB->get_record('workbook', array('id'=>$cm->instance), '*', MUST_EXIST);
} else {
    $d = required_param('d', PARAM_INT);     // workbook activity id
    $workbook = $DB->get_record('workbook', array('id'=>$d), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$workbook->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('workbook', $workbook->id, $course->id, null, MUST_EXIST);
}
$context = get_context_instance(CONTEXT_MODULE, $cm->id, MUST_EXIST);
require_login($course->id, false, $cm);
require_capability('mod/workbook:managetemplates', $context);
$PAGE->set_url(new moodle_url('/mod/workbook/preset.php', array('d'=>$workbook->id)));
$PAGE->set_title(get_string('course') . ': ' . $course->fullname);
$PAGE->set_heading($course->fullname);

// fill in missing properties needed for updating of instance
$workbook->course     = $cm->course;
$workbook->cmidnumber = $cm->idnumber;
$workbook->instance   = $cm->instance;

$presets = workbook_get_available_presets($context);
$canmanage = has_capability('mod/workbook:manageuserpresets', $context);
$strdelete = get_string('deleted', 'workbook');
foreach ($presets as &$preset) {
    if (!empty($preset->userid)) {
        $presetuser = $DB->get_record('user', array('id'=>$preset->userid), 'id,firstname,lastname', MUST_EXIST);
        $preset->description = $preset->name.' ('.fullname($presetuser, true).')';
    } else {
        $preset->userid = 0;
        $preset->description = $preset->name;
    }
    if ($preset->userid > 0 and ($preset->userid == $USER->id || $canmanage)) {
        $delurl = new moodle_url('/mod/workbook/preset.php', array('d'=> $workbook->id, 'action'=>'confirmdelete', 'fullname'=>$preset->userid.'/'.$preset->shortname, 'sesskey'=>sesskey()));
        $delicon = html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'class'=>'iconsmall', 'alt'=>$strdelete.' '.$preset->description));
        $preset->description .= html_writer::link($delurl, $delicon);
    }
}

$form_importexisting = new workbook_existing_preset_form(null, array('presets'=>$presets));
$form_importexisting->set_data(array('d' => $workbook->id));

$form_importzip = new workbook_import_preset_zip_form();
$form_importzip->set_data(array('d' => $workbook->id));

$form_export = new workbook_export_form();
$form_export->set_data(array('d' => $workbook->id));

$form_save = new workbook_save_preset_form();
$form_save->set_data(array('d' => $workbook->id, 'name'=>$workbook->name));

/* Output */
if (!$form_export->is_submitted()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($workbook->name));

    // Needed for tabs.php
    $currenttab = 'presets';
    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);
    include('tabs.php');
}

if (optional_param('sesskey', false, PARAM_BOOL) && confirm_sesskey()) {

    $renderer = $PAGE->get_renderer('mod_workbook');

    if ($formworkbook = $form_importexisting->get_data()) {
        $importer = new workbook_preset_existing_importer($course, $cm, $workbook, $formworkbook->fullname);
        echo $renderer->import_setting_mappings($workbook, $importer);
        echo $OUTPUT->footer();
    } else if ($formworkbook = $form_importzip->get_data()) {
        $file = new stdClass;
        $file->name = $form_importzip->get_new_filename('importfile');
        $file->path = $form_importzip->save_temp_file('importfile');
        $importer = new workbook_preset_upload_importer($course, $cm, $workbook, $file->path);
        echo $renderer->import_setting_mappings($workbook, $importer);
        echo $OUTPUT->footer();
        exit(0);
    } else if ($formworkbook = $form_export->get_data()) {

        if (headers_sent()) {
            print_error('headersent');
        }

        $exportfile = workbook_presets_export($course, $cm, $workbook);
        $exportfilename = basename($exportfile);
        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=\"$exportfilename\"");
        header('Expires: 0');
        header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
        header('Pragma: public');
        $exportfilehandler = fopen($exportfile, 'rb');
        print fread($exportfilehandler, filesize($exportfile));
        fclose($exportfilehandler);
        unlink($exportfile);
        exit(0);

    } else if ($formworkbook = $form_save->get_data()) {

        if (!empty($formworkbook->overwrite)) {
            workbook_delete_site_preset($formworkbook->name);
        }

        // If the preset exists now then we need to throw an error.
        $sitepresets = workbook_get_available_site_presets($context);
        foreach ($sitepresets as $key=>$preset) {
            if ($formworkbook->name == $preset->name) {
                print_error('errorpresetexists', 'preset');
            }
        }

        // Save the preset now
        workbook_presets_save($course, $cm, $workbook, $formworkbook->name);

        echo $OUTPUT->notification(get_string('savesuccess', 'workbook'), 'notifysuccess');
        echo $OUTPUT->continue_button($PAGE->url);
        echo $OUTPUT->footer();
        exit(0);
    } else {
        $action = optional_param('action', null, PARAM_ALPHA);
        $fullname = optional_param('fullname', '', PARAM_PATH); // directory the preset is in
        //
        // find out preset owner userid and shortname
        $parts = explode('/', $fullname, 2);
        $userid = empty($parts[0]) ? 0 : (int)$parts[0];
        $shortname = empty($parts[1]) ? '' : $parts[1];

        if ($userid && ($userid != $USER->id) && !has_capability('mod/workbook:viewalluserpresets', $context)) {
            print_error('cannotaccesspresentsother', 'workbook');
        }

        if ($action == 'confirmdelete') {
            $path = workbook_preset_path($course, $userid, $shortname);
            $strwarning = get_string('deletewarning', 'workbook').'<br />'.$shortname;
            $optionsyes = array('fullname' => $userid.'/'.$shortname,
                             'action' => 'delete',
                             'd' => $workbook->id);
            $optionsno = array('d' => $workbook->id);
            echo $OUTPUT->confirm($strwarning, new moodle_url('preset.php', $optionsyes), new moodle_url('preset.php', $optionsno));
            echo $OUTPUT->footer();
            exit(0);
        } else if ($action == 'delete') {
            if (!$userid || ($userid != $USER->id && !$canmanage)) {
               print_error('invalidrequest');
            }

            $presetpath = workbook_preset_path($course, $userid, $shortname);
            fulldelete($presetpath);

            $strdeleted = get_string('deleted', 'workbook');
            echo $OUTPUT->notification("$shortname $strdeleted", 'notifysuccess');
        } else if ($action == 'finishimport') {
            $overwritesettings = optional_param('overwritesettings', false, PARAM_BOOL);
            if (!$fullname) {
                $presetdir = $CFG->workbookroot.'/temp/forms/'.required_param('directory', PARAM_ALPHANUMEXT);
                if (!file_exists($presetdir) || !is_dir($presetdir)) {
                    print_error('cannotimport');
                }
                $importer = new workbook_preset_upload_importer($course, $cm, $workbook, $presetdir);
            } else {
                $importer = new workbook_preset_existing_importer($course, $cm, $workbook, $fullname);
            }
            $importer->import($overwritesettings);
            $strimportsuccess = get_string('importsuccess', 'workbook');
            $straddentries = get_string('addentries', 'workbook');
            $strtoworkbook = get_string('toworkbook', 'workbook');
            if (!$DB->get_records('workbook_records', array('workbookid'=>$workbook->id))) {
                echo $OUTPUT->notification("$strimportsuccess <a href='edit.php?d=$workbook->id'>$straddentries</a> $strtoworkbook", 'notifysuccess');
            } else {
                echo $OUTPUT->notification("$strimportsuccess", 'notifysuccess');
            }
        }
        echo $OUTPUT->continue_button($PAGE->url);
        echo $OUTPUT->footer();
        exit(0);
    }
}

// Export forms
echo $OUTPUT->heading(get_string('export', 'workbook'));
$form_export->display();
$form_save->display();

// Import forms
echo $OUTPUT->heading(get_string('import'));
$form_importzip->display();
$form_importexisting->display();

echo $OUTPUT->footer();