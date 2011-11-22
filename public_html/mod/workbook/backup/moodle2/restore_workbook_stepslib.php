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

/**
 * Define all the restore steps that will be used by the restore_workbook_activity_task
 */

/**
 * Structure step to restore one workbook activity
 */
class restore_workbook_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('workbook', '/activity/workbook');
        $paths[] = new restore_path_element('workbook_field', '/activity/workbook/fields/field');
        if ($userinfo) {
            $paths[] = new restore_path_element('workbook_record', '/activity/workbook/records/record');
            $paths[] = new restore_path_element('workbook_content', '/activity/workbook/records/record/contents/content');
            $paths[] = new restore_path_element('workbook_rating', '/activity/workbook/records/record/ratings/rating');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_workbook($workbook) {
        global $DB;

        $workbook = (object)$workbook;
        $oldid = $workbook->id;
        $workbook->course = $this->get_courseid();

        $workbook->timeavailablefrom = $this->apply_date_offset($workbook->timeavailablefrom);
        $workbook->timeavailableto = $this->apply_date_offset($workbook->timeavailableto);
        $workbook->timeviewfrom = $this->apply_date_offset($workbook->timeviewfrom);
        $workbook->timeviewto = $this->apply_date_offset($workbook->timeviewto);
        $workbook->assesstimestart = $this->apply_date_offset($workbook->assesstimestart);
        $workbook->assesstimefinish = $this->apply_date_offset($workbook->assesstimefinish);

        if ($workbook->scale < 0) { // scale found, get mapping
            $workbook->scale = -($this->get_mappingid('scale', abs($workbook->scale)));
        }

        // Some old backups can arrive with workbook->notification = null (MDL-24470)
        // convert them to proper column default (zero)
        if (is_null($workbook->notification)) {
            $workbook->notification = 0;
        }

        // insert the workbook record
        $newitemid = $DB->insert_record('workbook', $workbook);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_workbook_field($workbook) {
        global $DB;

        $workbook = (object)$workbook;
        $oldid = $workbook->id;

        $workbook->workbookid = $this->get_new_parentid('workbook');

        // insert the workbook_fields record
        $newitemid = $DB->insert_record('workbook_fields', $workbook);
        $this->set_mapping('workbook_field', $oldid, $newitemid, false); // no files associated
    }

    protected function process_workbook_record($workbook) {
        global $DB;

        $workbook = (object)$workbook;
        $oldid = $workbook->id;

        $workbook->timecreated = $this->apply_date_offset($workbook->timecreated);
        $workbook->timemodified = $this->apply_date_offset($workbook->timemodified);

        $workbook->userid = $this->get_mappingid('user', $workbook->userid);
        $workbook->groupid = $this->get_mappingid('group', $workbook->groupid);
        $workbook->workbookid = $this->get_new_parentid('workbook');

        // insert the workbook_records record
        $newitemid = $DB->insert_record('workbook_records', $workbook);
        $this->set_mapping('workbook_record', $oldid, $newitemid, false); // no files associated
    }

    protected function process_workbook_content($workbook) {
        global $DB;

        $workbook = (object)$workbook;
        $oldid = $workbook->id;

        $workbook->fieldid = $this->get_mappingid('workbook_field', $workbook->fieldid);
        $workbook->recordid = $this->get_new_parentid('workbook_record');

        // insert the workbook_content record
        $newitemid = $DB->insert_record('workbook_content', $workbook);
        $this->set_mapping('workbook_content', $oldid, $newitemid, true); // files by this itemname
    }

    protected function process_workbook_rating($workbook) {
        global $DB;

        $workbook = (object)$workbook;

        // Cannot use ratings API, cause, it's missing the ability to specify times (modified/created)
        $workbook->contextid = $this->task->get_contextid();
        $workbook->itemid    = $this->get_new_parentid('workbook_record');
        if ($workbook->scaleid < 0) { // scale found, get mapping
            $workbook->scaleid = -($this->get_mappingid('scale', abs($workbook->scaleid)));
        }
        $workbook->rating = $workbook->value;
        $workbook->userid = $this->get_mappingid('user', $workbook->userid);
        $workbook->timecreated = $this->apply_date_offset($workbook->timecreated);
        $workbook->timemodified = $this->apply_date_offset($workbook->timemodified);

        // We need to check that component and ratingarea are both set here.
        if (empty($workbook->component)) {
            $workbook->component = 'mod_workbook';
        }
        if (empty($workbook->ratingarea)) {
            $workbook->ratingarea = 'entry';
        }

        $newitemid = $DB->insert_record('rating', $workbook);
    }

    protected function after_execute() {
        global $DB;
        // Add workbook related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_workbook', 'intro', null);
        // Add content related files, matching by itemname (workbook_content)
        $this->add_related_files('mod_workbook', 'content', 'workbook_content');
        // Adjust the workbook->defaultsort field
        if ($defaultsort = $DB->get_field('workbook', 'defaultsort', array('id' => $this->get_new_parentid('workbook')))) {
            if ($defaultsort = $this->get_mappingid('workbook_field', $defaultsort)) {
                $DB->set_field('workbook', 'defaultsort', $defaultsort, array('id' => $this->get_new_parentid('workbook')));
            }
        }
    }
}
