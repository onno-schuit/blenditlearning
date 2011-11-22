<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 2005 Martin Dougiamas  http://dougiamas.com             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

// This file to be included so we can assume config.php has already been included.
// We also assume that $user, $course, $currenttab have been set


    if (empty($currenttab) or empty($workbook) or empty($course)) {
        print_error('cannotcallscript');
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $inactive = NULL;
    $activetwo = NULL;
    $tabs = array();
    $row = array();

    if (has_capability('mod/workbook:managetemplates', $context)) {
        $row[] = new tabobject('list', $CFG->wwwroot.'/mod/workbook/view.php?d='.$workbook->id, get_string('list','workbook'));
    }

    if (isset($record)) {
        $row[] = new tabobject('single', $CFG->wwwroot.'/mod/workbook/view.php?d='.$workbook->id.'&amp;rid='.$record->id, get_string('single','workbook'));
    } else {
        $row[] = new tabobject('single', $CFG->wwwroot.'/mod/workbook/view.php?d='.$workbook->id.'&amp;mode=single', get_string('single','workbook'));
    }

    // Add an advanced search tab.
    if (has_capability('mod/workbook:managetemplates', $context)) {
        $row[] = new tabobject('asearch', $CFG->wwwroot.'/mod/workbook/view.php?d='.$workbook->id.'&amp;mode=asearch', get_string('search', 'workbook'));
    }

    if (isloggedin()) { // just a perf shortcut
        if (workbook_user_can_add_entry($workbook, $currentgroup, $groupmode, $context)) { // took out participation list here!
            $addstring = empty($editentry) ? get_string('add', 'workbook') : get_string('editentry', 'workbook');
            $row[] = new tabobject('add', $CFG->wwwroot.'/mod/workbook/edit.php?d='.$workbook->id, $addstring);
        }
        if (has_capability(WORKBOOK_CAP_EXPORT, $context)) {
            // The capability required to Export workbook records is centrally defined in 'lib.php'
            // and should be weaker than those required to edit Templates, Fields and Presets.
            $row[] = new tabobject('export', $CFG->wwwroot.'/mod/workbook/export.php?d='.$workbook->id,
                         get_string('export', 'workbook'));
        }
        if (has_capability('mod/workbook:managetemplates', $context)) {
            if ($currenttab == 'list') {
                $defaultemplate = 'listtemplate';
            } else if ($currenttab == 'add') {
                $defaultemplate = 'addtemplate';
            } else if ($currenttab == 'asearch') {
                $defaultemplate = 'asearchtemplate';
            } else {
                $defaultemplate = 'singletemplate';
            }

            $row[] = new tabobject('templates', $CFG->wwwroot.'/mod/workbook/templates.php?d='.$workbook->id.'&amp;mode='.$defaultemplate,
                         get_string('templates','workbook'));
            $row[] = new tabobject('fields', $CFG->wwwroot.'/mod/workbook/field.php?d='.$workbook->id,
                         get_string('fields','workbook'));
            $row[] = new tabobject('presets', $CFG->wwwroot.'/mod/workbook/preset.php?d='.$workbook->id,
                         get_string('presets', 'workbook'));
        }
    }

    $tabs[] = $row;

    if ($currenttab == 'templates' and isset($mode)) {

        $inactive = array();
        $inactive[] = 'templates';
        $templatelist = array ('listtemplate', 'singletemplate', 'asearchtemplate', 'addtemplate', 'rsstemplate', 'csstemplate', 'jstemplate');

        $row  = array();
        $currenttab ='';
        foreach ($templatelist as $template) {
            $row[] = new tabobject($template, "templates.php?d=$workbook->id&amp;mode=$template", get_string($template, 'workbook'));
            if ($template == $mode) {
                $currenttab = $template;
            }
        }
        if ($currenttab == '') {
            $currenttab = $mode = 'singletemplate';
        }
        $tabs[] = $row;
        $activetwo = array('templates');
    }

// Print out the tabs and continue!
    print_tabs($tabs, $currenttab, $inactive, $activetwo);


