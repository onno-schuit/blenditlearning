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
require_once('export_form.php');

// workbook ID
$d = required_param('d', PARAM_INT);

$PAGE->set_url('/mod/workbook/export.php', array('d'=>$d));

if (! $workbook = $DB->get_record('workbook', array('id'=>$d))) {
    print_error('wrongworkbookid', 'workbook');
}

if (! $cm = get_coursemodule_from_instance('workbook', $workbook->id, $workbook->course)) {
    print_error('invalidcoursemodule');
}

if(! $course = $DB->get_record('course', array('id'=>$cm->course))) {
    print_error('invalidcourseid', '', '', $cm->course);
}

// fill in missing properties needed for updating of instance
$workbook->course     = $cm->course;
$workbook->cmidnumber = $cm->idnumber;
$workbook->instance   = $cm->instance;

if (! $context = get_context_instance(CONTEXT_MODULE, $cm->id)) {
    print_error('invalidcontext', '');
}

require_login($course->id, false, $cm);
require_capability(WORKBOOK_CAP_EXPORT, $context);

// get fields for this workbook
$fieldrecords = $DB->get_records('workbook_fields', array('workbookid'=>$workbook->id), 'id');

if(empty($fieldrecords)) {
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    if (has_capability('mod/workbook:managetemplates', $context)) {
        redirect($CFG->wwwroot.'/mod/workbook/field.php?d='.$workbook->id);
    } else {
        print_error('nofieldinworkbook', 'workbook');
    }
}

// populate objets for this workbooks fields
$fields = array();
foreach ($fieldrecords as $fieldrecord) {
    $fields[]= workbook_get_field($fieldrecord, $workbook);
}


$mform = new mod_workbook_export_form('export.php?d='.$workbook->id, $fields, $cm);

if($mform->is_cancelled()) {
    redirect('view.php?d='.$workbook->id);
} elseif (!$formworkbook = (array) $mform->get_data()) {
    // build header to match the rest of the UI
    $PAGE->set_title($workbook->name);
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($workbook->name));

    // these are for the tab display
    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);
    $currenttab = 'export';
    include('tabs.php');
    $mform->display();
    echo $OUTPUT->footer();
    die;
}

$selectedfields = array();
foreach ($formworkbook as $key => $value) {
    //field form elements are field_1 field_2 etc. 0 if not selected. 1 if selected.
    if (strpos($key, 'field_')===0 && !empty($value)) {
        $selectedfields[] = substr($key, 6);
    }
}

$exportworkbook = workbook_get_exportworkbook($workbook->id, $fields, $selectedfields);
$count = count($exportworkbook);
switch ($formworkbook['exporttype']) {
    case 'csv':
        workbook_export_csv($exportworkbook, $formworkbook['delimiter_name'], $workbook->name, $count);
        break;
    case 'xls':
        workbook_export_xls($exportworkbook, $workbook->name, $count);
        break;
    case 'ods':
        workbook_export_ods($exportworkbook, $workbook->name, $count);
        break;
}

die();
