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
 * @copyright 1990 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod-workbook
 */

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);   // course

$PAGE->set_url('/mod/workbook/index.php', array('id'=>$id));

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('invalidcourseid');
}

require_course_login($course);
$PAGE->set_pagelayout('incourse');

$context = get_context_instance(CONTEXT_COURSE, $course->id);

add_to_log($course->id, "workbook", "view all", "index.php?id=$course->id", "");

$strsectionname  = get_string('sectionname', 'format_'.$course->format);
$strname = get_string('name');
$strworkbook = get_string('modulename','workbook');
$strworkbookplural  = get_string('modulenameplural','workbook');

$PAGE->navbar->add($strworkbook, new moodle_url('/mod/workbook/index.php', array('id'=>$course->id)));
$PAGE->set_title($strworkbook);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

if (! $workbooks = get_all_instances_in_course("workbook", $course)) {
    notice(get_string('thereareno', 'moodle',$strworkbookplural) , "$CFG->wwwroot/course/view.php?id=$course->id");
}

$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    $sections = get_all_sections($course->id);
}

$timenow  = time();
$strname  = get_string('name');
$strdescription = get_string("description");
$strentries = get_string('entries', 'workbook');
$strnumnotapproved = get_string('numnotapproved', 'workbook');

$table = new html_table();

if ($usesections) {
    $table->head  = array ($strsectionname, $strname, $strdescription, $strentries, $strnumnotapproved);
    $table->align = array ('center', 'center', 'center', 'center', 'center');
} else {
    $table->head  = array ($strname, $strdescription, $strentries, $strnumnotapproved);
    $table->align = array ('center', 'center', 'center', 'center');
}

$rss = (!empty($CFG->enablerssfeeds) && !empty($CFG->workbook_enablerssfeeds));

if ($rss) {
    require_once($CFG->libdir."/rsslib.php");
    array_push($table->head, 'RSS');
    array_push($table->align, 'center');
}

$options = new stdClass();
$options->noclean = true;

$currentsection = "";

foreach ($workbooks as $workbook) {

    $printsection = "";

    //Calculate the href
    if (!$workbook->visible) {
        //Show dimmed if the mod is hidden
        $link = "<a class=\"dimmed\" href=\"view.php?id=$workbook->coursemodule\">".format_string($workbook->name,true)."</a>";
    } else {
        //Show normal if the mod is visible
        $link = "<a href=\"view.php?id=$workbook->coursemodule\">".format_string($workbook->name,true)."</a>";
    }

    // TODO: add group restricted counts here, and limit unapproved to ppl with approve cap only + link to approval page

    $numrecords = $DB->count_records_sql('SELECT COUNT(r.id) FROM {workbook_records} r WHERE r.workbookid =?', array($workbook->id));

    if ($workbook->approval == 1) {
        $numunapprovedrecords = $DB->count_records_sql('SELECT COUNT(r.id) FROM {workbook_records} r WHERE r.workbookid =? AND r.approved <> 1', array($workbook->id));
    } else {
        $numunapprovedrecords = '-';
    }

    $rsslink = '';
    if ($rss && $workbook->rssarticles > 0) {
        $rsslink = rss_get_link($context->id, $USER->id, 'mod_workbook', $workbook->id, 'RSS');
    }

    if ($usesections) {
        if ($workbook->section !== $currentsection) {
            if ($workbook->section) {
                $printsection = get_section_name($course, $sections[$workbook->section]);
            }
            if ($currentsection !== '') {
                $table->workbook[] = 'hr';
            }
            $currentsection = $workbook->section;
        }
        $row = array ($printsection, $link, format_text($workbook->intro, $workbook->introformat, $options), $numrecords, $numunapprovedrecords);

    } else {
        $row = array ($link, format_text($workbook->intro, $workbook->introformat, $options), $numrecords, $numunapprovedrecords);
    }

    if ($rss) {
        array_push($row, $rsslink);
    }

    $table->workbook[] = $row;
}

echo "<br />";
echo html_writer::tag('div', html_writer::table($table), array('class'=>'no-overflow'));
echo $OUTPUT->footer();

