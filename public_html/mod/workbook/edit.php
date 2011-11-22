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
 * This file is part of the Workbook module for Moodle
 *
 * @copyright 2005 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod-workbook
 */

require_once('../../config.php');
require_once('lib.php');
require_once("$CFG->libdir/rsslib.php");

$id    = optional_param('id', 0, PARAM_INT);    // course module id
$d     = optional_param('d', 0, PARAM_INT);    // workbook id
$rid   = optional_param('rid', 0, PARAM_INT);    //record id
$cancel   = optional_param('cancel', '', PARAM_RAW);    // cancel an add
$mode ='addtemplate';    //define the mode for this page, only 1 mode available

$url = new moodle_url('/mod/workbook/edit.php');
if ($rid !== 0) {
    $url->param('rid', $rid);
}
if ($cancel !== '') {
    $url->param('cancel', $cancel);
}

if ($id) {
    $url->param('id', $id);
    $PAGE->set_url($url);
    if (! $cm = get_coursemodule_from_id('workbook', $id)) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record('course', array('id'=>$cm->course))) {
        print_error('coursemisconf');
    }
    if (! $workbook = $DB->get_record('workbook', array('id'=>$cm->instance))) {
        print_error('invalidcoursemodule');
    }

} else {
    $url->param('d', $d);
    $PAGE->set_url($url);
    if (! $workbook = $DB->get_record('workbook', array('id'=>$d))) {
        print_error('invalidid', 'workbook');
    }
    if (! $course = $DB->get_record('course', array('id'=>$workbook->course))) {
        print_error('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance('workbook', $workbook->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}

require_login($course->id, false, $cm);

if (isguestuser()) {
    redirect('view.php?d='.$workbook->id);
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

/// If it's hidden then it doesn't show anything.  :)
if (empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context)) {
    $strworkbooks = get_string("modulenameplural", "workbook");

    $PAGE->set_title(format_string($workbook->name));
    $PAGE->set_heading(format_string($course->fullname));
    echo $OUTPUT->header();
    notice(get_string("activityiscurrentlyhidden"));
}

/// Can't use this if there are no fields
if (has_capability('mod/workbook:managetemplates', $context)) {
    if (!$DB->record_exists('workbook_fields', array('workbookid'=>$workbook->id))) {      // Brand new workbook!
        redirect($CFG->wwwroot.'/mod/workbook/field.php?d='.$workbook->id);  // Redirect to field entry
    }
}

if ($rid) {    // So do you have access?
    if (!(has_capability('mod/workbook:manageentries', $context) or workbook_isowner($rid)) or !confirm_sesskey() ) {
        print_error('noaccess','workbook');
    }
}

if ($cancel) {
    redirect('view.php?d='.$workbook->id);
}


/// RSS and CSS and JS meta
if (!empty($CFG->enablerssfeeds) && !empty($CFG->workbook_enablerssfeeds) && $workbook->rssarticles > 0) {
    $rsspath = rss_get_url($context->id, $USER->id, 'mod_workbook', $workbook->id);
    $PAGE->add_alternate_version(format_string($course->shortname) . ': %fullname%',
            $rsspath, 'application/rss+xml');
}
if ($workbook->csstemplate) {
    $PAGE->requires->css('/mod/workbook/css.php?d='.$workbook->id);
}
if ($workbook->jstemplate) {
    $PAGE->requires->js('/mod/workbook/js.php?d='.$workbook->id, true);
}

$possiblefields = $DB->get_records('workbook_fields', array('workbookid'=>$workbook->id), 'id');

foreach ($possiblefields as $field) {
    if ($field->type == 'file' || $field->type == 'picture') {
        require_once($CFG->dirroot.'/repository/lib.php');
        break;
    }
}

/// Define page variables
$strworkbook = get_string('modulenameplural','workbook');

if ($rid) {
    $PAGE->navbar->add(get_string('editentry', 'workbook'));
}

$PAGE->set_title($workbook->name);
$PAGE->set_heading($course->fullname);

/// Check to see if groups are being used here
$currentgroup = groups_get_activity_group($cm);
$groupmode = groups_get_activity_groupmode($cm);

if ($currentgroup) {
    $groupselect = " AND groupid = '$currentgroup'";
    $groupparam = "&amp;groupid=$currentgroup";
} else {
    $groupselect = "";
    $groupparam = "";
    $currentgroup = 0;
}


/// Process incoming workbook for adding/updating records

if ($workbookrecord = data_submitted() and confirm_sesskey()) {

    $ignorenames = array('MAX_FILE_SIZE','sesskey','d','rid','saveandview','cancel');  // strings to be ignored in input workbook

    if ($rid) {                                          /// Update some records

        /// All student edits are marked unapproved by default
        $record = $DB->get_record('workbook_records', array('id'=>$rid));

        /// reset approved flag after student edit
        if (!has_capability('mod/workbook:approve', $context)) {
            $record->approved = 0;
        }

        $record->groupid = $currentgroup;
        $record->timemodified = time();
        $DB->update_record('workbook_records', $record);

        /// Update all content
        $field = NULL;
        foreach ($workbookrecord as $name => $value) {
            if (!in_array($name, $ignorenames)) {
                $namearr = explode('_',$name);  // Second one is the field id
                if (empty($field->field) || ($namearr[1] != $field->field->id)) {  // Try to reuse classes
                    $field = workbook_get_field_from_id($namearr[1], $workbook);
                }
                if ($field) {
                    $field->update_content($rid, $value, $name);
                }
            }
        }

        add_to_log($course->id, 'workbook', 'update', "view.php?d=$workbook->id&amp;rid=$rid", $workbook->id, $cm->id);

        redirect($CFG->wwwroot.'/mod/workbook/view.php?d='.$workbook->id.'&rid='.$rid);

    } else { /// Add some new records

        if (!workbook_user_can_add_entry($workbook, $currentgroup, $groupmode, $context)) {
            print_error('cannotadd', 'workbook');
        }

    /// Check if maximum number of entry as specified by this workbook is reached
    /// Of course, you can't be stopped if you are an editting teacher! =)

        if (workbook_atmaxentries($workbook) and !has_capability('mod/workbook:manageentries',$context)){
            echo $OUTPUT->header();
            echo $OUTPUT->notification(get_string('atmaxentry','workbook'));
            echo $OUTPUT->footer();
            exit;
        }

        ///Empty form checking - you can't submit an empty form!

        $emptyform = true;      // assume the worst

        foreach ($workbookrecord as $name => $value) {
            if (!in_array($name, $ignorenames)) {
                $namearr = explode('_', $name);  // Second one is the field id
                if (empty($field->field) || ($namearr[1] != $field->field->id)) {  // Try to reuse classes
                    $field = workbook_get_field_from_id($namearr[1], $workbook);
                }
                if ($field->notemptyfield($value, $name)) {
                    $emptyform = false;
                    break;             // if anything has content, this form is not empty, so stop now!
                }
            }
        }

        if ($emptyform){    //nothing gets written to workbook
            echo $OUTPUT->notification(get_string('emptyaddform','workbook'));
        }

        if (!$emptyform && $recordid = workbook_add_record($workbook, $currentgroup)) {    //add instance to workbook_record

            /// Insert a whole lot of empty records to make sure we have them
            $fields = $DB->get_records('workbook_fields', array('workbookid'=>$workbook->id));
            foreach ($fields as $field) {
                $content->recordid = $recordid;
                $content->fieldid = $field->id;
                $DB->insert_record('workbook_content',$content);
            }

            /// For each field in the add form, add it to the workbook_content.
            foreach ($workbookrecord as $name => $value){
                if (!in_array($name, $ignorenames)) {
                    $namearr = explode('_', $name);  // Second one is the field id
                    if (empty($field->field) || ($namearr[1] != $field->field->id)) {  // Try to reuse classes
                        $field = workbook_get_field_from_id($namearr[1], $workbook);
                    }
                    if ($field) {
                        $field->update_content($recordid, $value, $name);
                    }
                }
            }

            add_to_log($course->id, 'workbook', 'add', "view.php?d=$workbook->id&amp;rid=$recordid", $workbook->id, $cm->id);

            if (!empty($workbookrecord->saveandview)) {
                redirect($CFG->wwwroot.'/mod/workbook/view.php?d='.$workbook->id.'&rid='.$recordid);
            }
        }
    }
}  // End of form processing


/// Print the page header

echo $OUTPUT->header();
groups_print_activity_menu($cm, $CFG->wwwroot.'/mod/workbook/edit.php?d='.$workbook->id);
echo $OUTPUT->heading(format_string($workbook->name));

/// Print the tabs

$currenttab = 'add';
if ($rid) {
    $editentry = true;  //used in tabs
}
include('tabs.php');


/// Print the browsing interface

$patterns = array();    //tags to replace
$replacement = array();    //html to replace those yucky tags

//form goes here first in case add template is empty
echo '<form enctype="multipart/form-workbook" action="edit.php" method="post">';
echo '<div>';
echo '<input name="d" value="'.$workbook->id.'" type="hidden" />';
echo '<input name="rid" value="'.$rid.'" type="hidden" />';
echo '<input name="sesskey" value="'.sesskey().'" type="hidden" />';
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');

if (!$rid){
    echo $OUTPUT->heading(get_string('newentry','workbook'), 2);
}

/******************************************
 * Regular expression replacement section *
 ******************************************/
if ($workbook->addtemplate){
    $possiblefields = $DB->get_records('workbook_fields', array('workbookid'=>$workbook->id), 'id');

    ///then we generate strings to replace
    foreach ($possiblefields as $eachfield){
        $field = workbook_get_field($eachfield, $workbook);
        $patterns[]="[[".$field->field->name."]]";
        $replacements[] = $field->display_add_field($rid);
        $patterns[]="[[".$field->field->name."#id]]";
        $replacements[] = 'field_'.$field->field->id;
    }
    $newtext = str_ireplace($patterns, $replacements, $workbook->{$mode});

} else {    //if the add template is not yet defined, print the default form!
    echo workbook_generate_default_template($workbook, 'addtemplate', $rid, true, false);
    $newtext = '';
}

echo $newtext;

echo '<div class="mdl-align"><input type="submit" name="saveandview" value="'.get_string('saveandview','workbook').'" />';
if ($rid) {
    echo '&nbsp;<input type="submit" name="cancel" value="'.get_string('cancel').'" onclick="javascript:history.go(-1)" />';
} else {
    if ((!$workbook->maxentries) || has_capability('mod/workbook:manageentries', $context) || (workbook_numentries($workbook) < ($workbook->maxentries - 1))) {
        echo '&nbsp;<input type="submit" value="'.get_string('saveandadd','workbook').'" />';
    }
}
echo '</div>';
echo $OUTPUT->box_end();
echo '</div></form>';


/// Finish the page

// Print the stuff that need to come after the form fields.
if (!$fields = $DB->get_records('workbook_fields', array('workbookid'=>$workbook->id))) {
    print_error('nofieldinworkbook', 'workbook');
}
foreach ($fields as $eachfield) {
    $field = workbook_get_field($eachfield, $workbook);
    $field->print_after_form();
}

echo $OUTPUT->footer();
