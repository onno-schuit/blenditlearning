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

$id             = optional_param('id', 0, PARAM_INT);            // course module id
$d              = optional_param('d', 0, PARAM_INT);             // workbook id
$fid            = optional_param('fid', 0 , PARAM_INT);          // update field id
$newtype        = optional_param('newtype','',PARAM_ALPHA);      // type of the new field
$mode           = optional_param('mode','',PARAM_ALPHA);
$defaultsort    = optional_param('defaultsort', 0, PARAM_INT);
$defaultsortdir = optional_param('defaultsortdir', 0, PARAM_INT);
$cancel         = optional_param('cancel', 0, PARAM_BOOL);

if ($cancel) {
    $mode = 'list';
}

$url = new moodle_url('/mod/workbook/field.php');
if ($fid !== 0) {
    $url->param('fid', $fid);
}
if ($newtype !== '') {
    $url->param('newtype', $newtype);
}
if ($mode !== '') {
    $url->param('mode', $mode);
}
if ($defaultsort !== 0) {
    $url->param('defaultsort', $defaultsort);
}
if ($defaultsortdir !== 0) {
    $url->param('defaultsortdir', $defaultsortdir);
}
if ($cancel !== 0) {
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
        print_error('invalidcoursemodule');
    }
    if (! $cm = get_coursemodule_from_instance('workbook', $workbook->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}

require_login($course->id, true, $cm);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('mod/workbook:managetemplates', $context);

/************************************
 *        Workbook Processing           *
 ***********************************/
switch ($mode) {

    case 'add':    ///add a new field
        if (confirm_sesskey() and $fieldinput = data_submitted()){

            //$fieldinput->name = workbook_clean_field_name($fieldinput->name);

        /// Only store this new field if it doesn't already exist.
            if (($fieldinput->name == '') or workbook_fieldname_exists($fieldinput->name, $workbook->id)) {

                $displaynoticebad = get_string('invalidfieldname','workbook');

            } else {

            /// Check for arrays and convert to a comma-delimited string
                workbook_convert_arrays_to_strings($fieldinput);

            /// Create a field object to collect and store the workbook safely
                $type = required_param('type', PARAM_FILE);
                $field = workbook_get_field_new($type, $workbook);

                $field->define_field($fieldinput);
                $field->insert_field();

            /// Update some templates
                workbook_append_new_field_to_templates($workbook, $fieldinput->name);

                add_to_log($course->id, 'workbook', 'fields add',
                           "field.php?d=$workbook->id&amp;mode=display&amp;fid=$fid", $fid, $cm->id);

                $displaynoticegood = get_string('fieldadded','workbook');
            }
        }
        break;


    case 'update':    ///update a field
        if (confirm_sesskey() and $fieldinput = data_submitted()){

            //$fieldinput->name = workbook_clean_field_name($fieldinput->name);

            if (($fieldinput->name == '') or workbook_fieldname_exists($fieldinput->name, $workbook->id, $fieldinput->fid)) {

                $displaynoticebad = get_string('invalidfieldname','workbook');

            } else {
            /// Check for arrays and convert to a comma-delimited string
                workbook_convert_arrays_to_strings($fieldinput);

            /// Create a field object to collect and store the workbook safely
                $field = workbook_get_field_from_id($fid, $workbook);
                $oldfieldname = $field->field->name;

                $field->field->name = $fieldinput->name;
                $field->field->description = $fieldinput->description;

                for ($i=1; $i<=10; $i++) {
                    if (isset($fieldinput->{'param'.$i})) {
                        $field->field->{'param'.$i} = $fieldinput->{'param'.$i};
                    } else {
                        $field->field->{'param'.$i} = '';
                    }
                }

                $field->update_field();

            /// Update the templates.
                workbook_replace_field_in_templates($workbook, $oldfieldname, $field->field->name);

                add_to_log($course->id, 'workbook', 'fields update',
                           "field.php?d=$workbook->id&amp;mode=display&amp;fid=$fid", $fid, $cm->id);

                $displaynoticegood = get_string('fieldupdated','workbook');
            }
        }
        break;


    case 'delete':    // Delete a field
        if (confirm_sesskey()){

            if ($confirm = optional_param('confirm', 0, PARAM_INT)) {


                // Delete the field completely
                if ($field = workbook_get_field_from_id($fid, $workbook)) {
                    $field->delete_field();

                    // Update the templates.
                    workbook_replace_field_in_templates($workbook, $field->field->name, '');

                    // Update the default sort field
                    if ($fid == $workbook->defaultsort) {
                        $rec = new stdClass();
                        $rec->id = $workbook->id;
                        $rec->defaultsort = 0;
                        $rec->defaultsortdir = 0;
                        $DB->update_record('workbook', $rec);
                    }

                    add_to_log($course->id, 'workbook', 'fields delete',
                               "field.php?d=$workbook->id", $field->field->name, $cm->id);

                    $displaynoticegood = get_string('fielddeleted', 'workbook');
                }

            } else {

                workbook_print_header($course,$cm,$workbook, false);

                // Print confirmation message.
                $field = workbook_get_field_from_id($fid, $workbook);

                echo $OUTPUT->confirm('<strong>'.$field->name().': '.$field->field->name.'</strong><br /><br />'. get_string('confirmdeletefield','workbook'),
                             'field.php?d='.$workbook->id.'&mode=delete&fid='.$fid.'&confirm=1',
                             'field.php?d='.$workbook->id);

                echo $OUTPUT->footer();
                exit;
            }
        }
        break;


    case 'sort':    // Set the default sort parameters
        if (confirm_sesskey()) {
            $rec = new stdClass();
            $rec->id = $workbook->id;
            $rec->defaultsort = $defaultsort;
            $rec->defaultsortdir = $defaultsortdir;

            $DB->update_record('workbook', $rec);
            redirect($CFG->wwwroot.'/mod/workbook/field.php?d='.$workbook->id, get_string('changessaved'), 2);
            exit;
        }
        break;

    default:
        break;
}



/// Print the browsing interface

///get the list of possible fields (plugins)
$directories = get_list_of_plugins('mod/workbook/field/');
$menufield = array();

foreach ($directories as $directory){
    $menufield[$directory] = get_string($directory,'workbook');    //get from language files
}
asort($menufield);    //sort in alphabetical order
$PAGE->set_title(get_string('course') . ': ' . $course->fullname);
$PAGE->set_heading($course->fullname);

$PAGE->set_pagetype('mod-workbook-field-' . $newtype);
if (($mode == 'new') && (!empty($newtype)) && confirm_sesskey()) {          ///  Adding a new field
    workbook_print_header($course, $cm, $workbook,'fields');

    $field = workbook_get_field_new($newtype, $workbook);
    $field->display_edit_field();

} else if ($mode == 'display' && confirm_sesskey()) { /// Display/edit existing field
    workbook_print_header($course, $cm, $workbook,'fields');

    $field = workbook_get_field_from_id($fid, $workbook);
    $field->display_edit_field();

} else {                                              /// Display the main listing of all fields
    workbook_print_header($course, $cm, $workbook,'fields');

    if (!$DB->record_exists('workbook_fields', array('workbookid'=>$workbook->id))) {
        echo $OUTPUT->notification(get_string('nofieldinworkbook','workbook'));  // nothing in workbook
        echo $OUTPUT->notification(get_string('pleaseaddsome','workbook', 'preset.php?id='.$cm->id));      // link to presets

    } else {    //else print quiz style list of fields

        $table = new html_table();
        $table->head = array(get_string('fieldname','workbook'), get_string('type','workbook'), get_string('fielddescription', 'workbook'), get_string('action','workbook'));
        $table->align = array('left','left','left', 'center');
        $table->wrap = array(false,false,false,false);

        if ($fff = $DB->get_records('workbook_fields', array('workbookid'=>$workbook->id),'id')){
            foreach ($fff as $ff) {

                $field = workbook_get_field($ff, $workbook);

                $table->data[] = array(

                '<a href="field.php?mode=display&amp;d='.$workbook->id.
                '&amp;fid='.$field->field->id.'&amp;sesskey='.sesskey().'">'.$field->field->name.'</a>',

                $field->image().'&nbsp;'.get_string($field->type, 'workbook'),

                shorten_text($field->field->description, 30),

                '<a href="field.php?d='.$workbook->id.'&amp;mode=display&amp;fid='.$field->field->id.'&amp;sesskey='.sesskey().'">'.
                '<img src="'.$OUTPUT->pix_url('t/edit') . '" class="iconsmall" alt="'.get_string('edit').'" title="'.get_string('edit').'" /></a>'.
                '&nbsp;'.
                '<a href="field.php?d='.$workbook->id.'&amp;mode=delete&amp;fid='.$field->field->id.'&amp;sesskey='.sesskey().'">'.
                '<img src="'.$OUTPUT->pix_url('t/delete') . '" class="iconsmall" alt="'.get_string('delete').'" title="'.get_string('delete').'" /></a>'

                );
            }
        }
        echo html_writer::table($table);
    }


    echo '<div class="fieldadd">';
    echo '<label for="fieldform_jump">'.get_string('newfield','workbook').'</label>';
    $popupurl = $CFG->wwwroot.'/mod/workbook/field.php?d='.$workbook->id.'&mode=new&sesskey='.  sesskey();
    echo $OUTPUT->single_select(new moodle_url($popupurl), 'newtype', $menufield, null, array(''=>'choosedots'), 'fieldform');
    echo $OUTPUT->help_icon('newfield', 'workbook');
    echo '</div>';

    echo '<div class="sortdefault">';
    echo '<form id="sortdefault" action="'.$CFG->wwwroot.'/mod/workbook/field.php" method="get">';
    echo '<div>';
    echo '<input type="hidden" name="d" value="'.$workbook->id.'" />';
    echo '<input type="hidden" name="mode" value="sort" />';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<label for="defaultsort">'.get_string('defaultsortfield','workbook').'</label>';
    echo '<select id="defaultsort" name="defaultsort">';
    if ($fields = $DB->get_records('workbook_fields', array('workbookid'=>$workbook->id))) {
        echo '<optgroup label="'.get_string('fields', 'workbook').'">';
        foreach ($fields as $field) {
            if ($workbook->defaultsort == $field->id) {
                echo '<option value="'.$field->id.'" selected="selected">'.$field->name.'</option>';
            } else {
                echo '<option value="'.$field->id.'">'.$field->name.'</option>';
            }
        }
        echo '</optgroup>';
    }
    $options = array();
    $options[WORKBOOK_TIMEADDED]    = get_string('timeadded', 'workbook');
// TODO: we will need to change defaultsort db to unsinged to make these work in 2.0
/*        $options[WORKBOOK_TIMEMODIFIED] = get_string('timemodified', 'workbook');
    $options[WORKBOOK_FIRSTNAME]    = get_string('authorfirstname', 'workbook');
    $options[WORKBOOK_LASTNAME]     = get_string('authorlastname', 'workbook');
    if ($workbook->approval and has_capability('mod/workbook:approve', $context)) {
        $options[WORKBOOK_APPROVED] = get_string('approved', 'workbook');
    }*/
    echo '<optgroup label="'.get_string('other', 'workbook').'">';
    foreach ($options as $key => $name) {
        if ($workbook->defaultsort == $key) {
            echo '<option value="'.$key.'" selected="selected">'.$name.'</option>';
        } else {
            echo '<option value="'.$key.'">'.$name.'</option>';
        }
    }
    echo '</optgroup>';
    echo '</select>';

    $options = array(0 => get_string('ascending', 'workbook'),
                     1 => get_string('descending', 'workbook'));
    echo html_writer::select($options, 'defaultsortdir', $workbook->defaultsortdir, false);
    echo '<input type="submit" value="'.get_string('save', 'workbook').'" />';
    echo '</div>';
    echo '</form>';
    echo '</div>';

}

/// Finish the page
echo $OUTPUT->footer();

