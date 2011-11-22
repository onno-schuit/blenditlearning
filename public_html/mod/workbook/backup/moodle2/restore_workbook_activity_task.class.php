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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/workbook/backup/moodle2/restore_workbook_stepslib.php'); // Because it exists (must)

/**
 * workbook restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_workbook_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Workbook only has one structure step
        $this->add_step(new restore_workbook_activity_structure_step('workbook_structure', 'workbook.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('workbook', array(
                              'intro', 'singletemplate', 'listtemplate', 'listtemplateheader', 'listtemplatefooter',
                              'addtemplate', 'rsstemplate', 'rsstitletemplate', 'asearchtemplate'), 'workbook');
        $contents[] = new restore_decode_content('workbook_fields', array(
                              'description', 'param1', 'param2', 'param3',
                              'param4', 'param5', 'param6', 'param7',
                              'param8', 'param9', 'param10'), 'workbook_field');
        $contents[] = new restore_decode_content('workbook_content', array(
                              'content', 'content1', 'content2', 'content3', 'content4'));

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('DATAVIEWBYID', '/mod/workbook/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('DATAVIEWBYD', '/mod/workbook/index.php?d=$1', 'workbook');
        $rules[] = new restore_decode_rule('DATAINDEX', '/mod/workbook/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('DATAVIEWRECORD', '/mod/workbook/view.php?d=$1&amp;rid=$2', array('workbook', 'workbook_record'));

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * workbook logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('workbook', 'add', 'view.php?d={workbook}&rid={workbook_record}', '{workbook}');
        $rules[] = new restore_log_rule('workbook', 'update', 'view.php?d={workbook}&rid={workbook_record}', '{workbook}');
        $rules[] = new restore_log_rule('workbook', 'view', 'view.php?id={course_module}', '{workbook}');
        $rules[] = new restore_log_rule('workbook', 'record delete', 'view.php?id={course_module}', '{workbook}');
        $rules[] = new restore_log_rule('workbook', 'fields add', 'field.php?d={workbook}&mode=display&fid={workbook_field}', '{workbook_field}');
        $rules[] = new restore_log_rule('workbook', 'fields update', 'field.php?d={workbook}&mode=display&fid={workbook_field}', '{workbook_field}');
        $rules[] = new restore_log_rule('workbook', 'fields delete', 'field.php?d={workbook}', '[name]');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('workbook', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
