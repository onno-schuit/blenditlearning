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
 * @package   mod-workbook
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Some constants
define ('WORKBOOK_MAX_ENTRIES', 50);
define ('WORKBOOK_PERPAGE_SINGLE', 1);

define ('WORKBOOK_FIRSTNAME', -1);
define ('WORKBOOK_LASTNAME', -2);
define ('WORKBOOK_APPROVED', -3);
define ('WORKBOOK_TIMEADDED', 0);
define ('WORKBOOK_TIMEMODIFIED', -4);

define ('WORKBOOK_CAP_EXPORT', 'mod/workbook:viewalluserpresets');

define('WORKBOOK_PRESET_COMPONENT', 'mod_workbook');
define('WORKBOOK_PRESET_FILEAREA', 'site_presets');
define('WORKBOOK_PRESET_CONTEXT', SYSCONTEXTID);

// Users having assigned the default role "Non-editing teacher" can export workbook records
// Using the mod/workbook capability "viewalluserpresets" existing in Moodle 1.9.x.
// In Moodle >= 2, new roles may be introduced and used instead.

/**
 * @package   mod-workbook
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class workbook_field_base {     // Base class for Workbook Field Types (see field/*/field.class.php)

    /** @var string Subclasses must override the type with their name */
    var $type = 'unknown';
    /** @var object The workbook object that this field belongs to */
    var $workbook = NULL;
    /** @var object The field object itself, if we know it */
    var $field = NULL;
    /** @var int Width of the icon for this fieldtype */
    var $iconwidth = 16;
    /** @var int Width of the icon for this fieldtype */
    var $iconheight = 16;
    /** @var object course module or cmifno */
    var $cm;
    /** @var object activity context */
    var $context;

    /**
     * Constructor function
     *
     * @global object
     * @uses CONTEXT_MODULE
     * @param int $field
     * @param int $workbook
     * @param int $cm
     */
    function __construct($field=0, $workbook=0, $cm=0) {   // Field or workbook or both, each can be id or object
        global $DB;

        if (empty($field) && empty($workbook)) {
            print_error('missingfield', 'workbook');
        }

        if (!empty($field)) {
            if (is_object($field)) {
                $this->field = $field;  // Programmer knows what they are doing, we hope
            } else if (!$this->field = $DB->get_record('workbook_fields', array('id'=>$field))) {
                print_error('invalidfieldid', 'workbook');
            }
            if (empty($workbook)) {
                if (!$this->workbook = $DB->get_record('workbook', array('id'=>$this->field->workbookid))) {
                    print_error('invalidid', 'workbook');
                }
            }
        }

        if (empty($this->workbook)) {         // We need to define this properly
            if (!empty($workbook)) {
                if (is_object($workbook)) {
                    $this->workbook = $workbook;  // Programmer knows what they are doing, we hope
                } else if (!$this->workbook = $DB->get_record('workbook', array('id'=>$workbook))) {
                    print_error('invalidid', 'workbook');
                }
            } else {                      // No way to define it!
                print_error('missingworkbook', 'workbook');
            }
        }

        if ($cm) {
            $this->cm = $cm;
        } else {
            $this->cm = get_coursemodule_from_instance('workbook', $this->workbook->id);
        }

        if (empty($this->field)) {         // We need to define some default values
            $this->define_default_field();
        }

        $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
    }


    /**
     * This field just sets up a default field object
     *
     * @return bool
     */
    function define_default_field() {
        global $OUTPUT;
        if (empty($this->workbook->id)) {
            echo $OUTPUT->notification('Programmer error: workbookid not defined in field class');
        }
        $this->field = new stdClass();
        $this->field->id = 0;
        $this->field->workbookid = $this->workbook->id;
        $this->field->type   = $this->type;
        $this->field->param1 = '';
        $this->field->param2 = '';
        $this->field->param3 = '';
        $this->field->name = '';
        $this->field->description = '';

        return true;
    }

    /**
     * Set up the field object according to workbook in an object.  Now is the time to clean it!
     *
     * @return bool
     */
    function define_field($workbook) {
        $this->field->type        = $this->type;
        $this->field->workbookid      = $this->workbook->id;

        $this->field->name        = trim($workbook->name);
        $this->field->description = trim($workbook->description);

        if (isset($workbook->param1)) {
            $this->field->param1 = trim($workbook->param1);
        }
        if (isset($workbook->param2)) {
            $this->field->param2 = trim($workbook->param2);
        }
        if (isset($workbook->param3)) {
            $this->field->param3 = trim($workbook->param3);
        }
        if (isset($workbook->param4)) {
            $this->field->param4 = trim($workbook->param4);
        }
        if (isset($workbook->param5)) {
            $this->field->param5 = trim($workbook->param5);
        }

        return true;
    }

    /**
     * Insert a new field in the workbook
     * We assume the field object is already defined as $this->field
     *
     * @global object
     * @return bool
     */
    function insert_field() {
        global $DB, $OUTPUT;

        if (empty($this->field)) {
            echo $OUTPUT->notification('Programmer error: Field has not been defined yet!  See define_field()');
            return false;
        }

        $this->field->id = $DB->insert_record('workbook_fields',$this->field);
        return true;
    }


    /**
     * Update a field in the workbook
     *
     * @global object
     * @return bool
     */
    function update_field() {
        global $DB;

        $DB->update_record('workbook_fields', $this->field);
        return true;
    }

    /**
     * Delete a field completely
     *
     * @global object
     * @return bool
     */
    function delete_field() {
        global $DB;

        if (!empty($this->field->id)) {
            $this->delete_content();
            $DB->delete_records('workbook_fields', array('id'=>$this->field->id));
        }
        return true;
    }

    /**
     * Print the relevant form element in the ADD template for this field
     *
     * @global object
     * @param int $recordid
     * @return string
     */
    function display_add_field($recordid=0){
        global $DB;

        if ($recordid){
            $content = $DB->get_field('workbook_content', 'content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid));
        } else {
            $content = '';
        }

        // beware get_field returns false for new, empty records MDL-18567
        if ($content===false) {
            $content='';
        }

        $str = '<div title="'.s($this->field->description).'">';
        $str .= '<input style="width:300px;" type="text" name="field_'.$this->field->id.'" id="field_'.$this->field->id.'" value="'.s($content).'" />';
        $str .= '</div>';

        return $str;
    }

    /**
     * Print the relevant form element to define the attributes for this field
     * viewable by teachers only.
     *
     * @global object
     * @global object
     * @return void Output is echo'd
     */
    function display_edit_field() {
        global $CFG, $DB, $OUTPUT;

        if (empty($this->field)) {   // No field has been defined yet, try and make one
            $this->define_default_field();
        }
        echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');

        echo '<form id="editfield" action="'.$CFG->wwwroot.'/mod/workbook/field.php" method="post">'."\n";
        echo '<input type="hidden" name="d" value="'.$this->workbook->id.'" />'."\n";
        if (empty($this->field->id)) {
            echo '<input type="hidden" name="mode" value="add" />'."\n";
            $savebutton = get_string('add');
        } else {
            echo '<input type="hidden" name="fid" value="'.$this->field->id.'" />'."\n";
            echo '<input type="hidden" name="mode" value="update" />'."\n";
            $savebutton = get_string('savechanges');
        }
        echo '<input type="hidden" name="type" value="'.$this->type.'" />'."\n";
        echo '<input name="sesskey" value="'.sesskey().'" type="hidden" />'."\n";

        echo $OUTPUT->heading($this->name());

        require_once($CFG->dirroot.'/mod/workbook/field/'.$this->type.'/mod.html');

        echo '<div class="mdl-align">';
        echo '<input type="submit" value="'.$savebutton.'" />'."\n";
        echo '<input type="submit" name="cancel" value="'.get_string('cancel').'" />'."\n";
        echo '</div>';

        echo '</form>';

        echo $OUTPUT->box_end();
    }

    /**
     * Display the content of the field in browse mode
     *
     * @global object
     * @param int $recordid
     * @param object $template
     * @return bool|string
     */
    function display_browse_field($recordid, $template) {
        global $DB;

        if ($content = $DB->get_record('workbook_content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid))) {
            if (isset($content->content)) {
                $options = new stdClass();
                if ($this->field->param1 == '1') {  // We are autolinking this field, so disable linking within us
                    //$content->content = '<span class="nolink">'.$content->content.'</span>';
                    //$content->content1 = FORMAT_HTML;
                    $options->filter=false;
                }
                $options->para = false;
                $str = format_text($content->content, $content->content1, $options);
            } else {
                $str = '';
            }
            return $str;
        }
        return false;
    }

    /**
     * Update the content of one workbook field in the workbook_content table
     * @global object
     * @param int $recordid
     * @param mixed $value
     * @param string $name
     * @return bool
     */
    function update_content($recordid, $value, $name=''){
        global $DB;

        $content = new stdClass();
        $content->fieldid = $this->field->id;
        $content->recordid = $recordid;
        $content->content = clean_param($value, PARAM_NOTAGS);

        if ($oldcontent = $DB->get_record('workbook_content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid))) {
            $content->id = $oldcontent->id;
            return $DB->update_record('workbook_content', $content);
        } else {
            return $DB->insert_record('workbook_content', $content);
        }
    }

    /**
     * Delete all content associated with the field
     *
     * @global object
     * @param int $recordid
     * @return bool
     */
    function delete_content($recordid=0) {
        global $DB;

        if ($recordid) {
            $conditions = array('fieldid'=>$this->field->id, 'recordid'=>$recordid);
        } else {
            $conditions = array('fieldid'=>$this->field->id);
        }

        $rs = $DB->get_recordset('workbook_content', $conditions);
        if ($rs->valid()) {
            $fs = get_file_storage();
            foreach ($rs as $content) {
                $fs->delete_area_files($this->context->id, 'mod_workbook', 'content', $content->id);
            }
        }
        $rs->close();

        return $DB->delete_records('workbook_content', $conditions);
    }

    /**
     * Check if a field from an add form is empty
     *
     * @param mixed $value
     * @param mixed $name
     * @return bool
     */
    function notemptyfield($value, $name) {
        return !empty($value);
    }

    /**
     * Just in case a field needs to print something before the whole form
     */
    function print_before_form() {
    }

    /**
     * Just in case a field needs to print something after the whole form
     */
    function print_after_form() {
    }


    /**
     * Returns the sortable field for the content. By default, it's just content
     * but for some plugins, it could be content 1 - content4
     *
     * @return string
     */
    function get_sort_field() {
        return 'content';
    }

    /**
     * Returns the SQL needed to refer to the column.  Some fields may need to CAST() etc.
     *
     * @param string $fieldname
     * @return string $fieldname
     */
    function get_sort_sql($fieldname) {
        return $fieldname;
    }

    /**
     * Returns the name/type of the field
     *
     * @return string
     */
    function name() {
        return get_string('name'.$this->type, 'workbook');
    }

    /**
     * Prints the respective type icon
     *
     * @global object
     * @return string
     */
    function image() {
        global $OUTPUT;

        $params = array('d'=>$this->workbook->id, 'fid'=>$this->field->id, 'mode'=>'display', 'sesskey'=>sesskey());
        $link = new moodle_url('/mod/workbook/field.php', $params);
        $str = '<a href="'.$link->out().'">';
        $str .= '<img src="'.$OUTPUT->pix_url('field/'.$this->type, 'workbook') . '" ';
        $str .= 'height="'.$this->iconheight.'" width="'.$this->iconwidth.'" alt="'.$this->type.'" title="'.$this->type.'" /></a>';
        return $str;
    }

    /**
     * Per default, it is assumed that fields support text exporting.
     * Override this (return false) on fields not supporting text exporting.
     *
     * @return bool true
     */
    function text_export_supported() {
        return true;
    }

    /**
     * Per default, return the record's text value only from the "content" field.
     * Override this in fields class if necesarry.
     *
     * @param string $record
     * @return string
     */
    function export_text_value($record) {
        if ($this->text_export_supported()) {
            return $record->content;
        }
    }

    /**
     * @param string $relativepath
     * @return bool false
     */
    function file_ok($relativepath) {
        return false;
    }
}


/**
 * Given a template and a workbookid, generate a default case template
 *
 * @global object
 * @param object $workbook
 * @param string template [addtemplate, singletemplate, listtempalte, rsstemplate]
 * @param int $recordid
 * @param bool $form
 * @param bool $update
 * @return bool|string
 */
function workbook_generate_default_template(&$workbook, $template, $recordid=0, $form=false, $update=true) {
    global $DB;

    if (!$workbook && !$template) {
        return false;
    }
    if ($template == 'csstemplate' or $template == 'jstemplate' ) {
        return '';
    }

    // get all the fields for that workbook
    if ($fields = $DB->get_records('workbook_fields', array('workbookid'=>$workbook->id), 'id')) {

        $table = new html_table();
        $table->attributes['class'] = 'mod-workbook-default-template';
        $table->colclasses = array('template-field', 'template-token');
        $table->workbook = array();
        foreach ($fields as $field) {
            if ($form) {   // Print forms instead of workbook
                $fieldobj = workbook_get_field($field, $workbook);
                $token = $fieldobj->display_add_field($recordid);
            } else {           // Just print the tag
                $token = '[['.$field->name.']]';
            }
            $table->workbook[] = array(
                $field->name.': ',
                $token
            );
        }
        if ($template == 'listtemplate') {
            $cell = new html_table_cell('##edit##  ##more##  ##delete##  ##approve##  ##export##');
            $cell->colspan = 2;
            $cell->attributes['class'] = 'controls';
            $table->workbook[] = new html_table_row(array($cell));
        } else if ($template == 'singletemplate') {
            $cell = new html_table_cell('##edit##  ##delete##  ##approve##  ##export##');
            $cell->colspan = 2;
            $cell->attributes['class'] = 'controls';
            $table->workbook[] = new html_table_row(array($cell));
        } else if ($template == 'asearchtemplate') {
            $row = new html_table_row(array(get_string('authorfirstname', 'workbook').': ', '##firstname##'));
            $row->attributes['class'] = 'searchcontrols';
            $table->workbook[] = $row;
            $row = new html_table_row(array(get_string('authorlastname', 'workbook').': ', '##lastname##'));
            $row->attributes['class'] = 'searchcontrols';
            $table->workbook[] = $row;
        }

        $str  = html_writer::start_tag('div', array('class' => 'defaulttemplate'));
        $str .= html_writer::table($table);
        $str .= html_writer::end_tag('div');
        if ($template == 'listtemplate'){
            $str .= html_writer::empty_tag('hr');
        }

        if ($update) {
            $newworkbook = new stdClass();
            $newworkbook->id = $workbook->id;
            $newworkbook->{$template} = $str;
            $DB->update_record('workbook', $newworkbook);
            $workbook->{$template} = $str;
        }

        return $str;
    }
}


/**
 * Search for a field name and replaces it with another one in all the
 * form templates. Set $newfieldname as '' if you want to delete the
 * field from the form.
 *
 * @global object
 * @param object $workbook
 * @param string $searchfieldname
 * @param string $newfieldname
 * @return bool
 */
function workbook_replace_field_in_templates($workbook, $searchfieldname, $newfieldname) {
    global $DB;

    if (!empty($newfieldname)) {
        $prestring = '[[';
        $poststring = ']]';
        $idpart = '#id';

    } else {
        $prestring = '';
        $poststring = '';
        $idpart = '';
    }

    $newworkbook = new stdClass();
    $newworkbook->id = $workbook->id;
    $newworkbook->singletemplate = str_ireplace('[['.$searchfieldname.']]',
            $prestring.$newfieldname.$poststring, $workbook->singletemplate);

    $newworkbook->listtemplate = str_ireplace('[['.$searchfieldname.']]',
            $prestring.$newfieldname.$poststring, $workbook->listtemplate);

    $newworkbook->addtemplate = str_ireplace('[['.$searchfieldname.']]',
            $prestring.$newfieldname.$poststring, $workbook->addtemplate);

    $newworkbook->addtemplate = str_ireplace('[['.$searchfieldname.'#id]]',
            $prestring.$newfieldname.$idpart.$poststring, $workbook->addtemplate);

    $newworkbook->rsstemplate = str_ireplace('[['.$searchfieldname.']]',
            $prestring.$newfieldname.$poststring, $workbook->rsstemplate);

    return $DB->update_record('workbook', $newworkbook);
}


/**
 * Appends a new field at the end of the form template.
 *
 * @global object
 * @param object $workbook
 * @param string $newfieldname
 */
function workbook_append_new_field_to_templates($workbook, $newfieldname) {
    global $DB;

    $newworkbook = new stdClass();
    $newworkbook->id = $workbook->id;
    $change = false;

    if (!empty($workbook->singletemplate)) {
        $newworkbook->singletemplate = $workbook->singletemplate.' [[' . $newfieldname .']]';
        $change = true;
    }
    if (!empty($workbook->addtemplate)) {
        $newworkbook->addtemplate = $workbook->addtemplate.' [[' . $newfieldname . ']]';
        $change = true;
    }
    if (!empty($workbook->rsstemplate)) {
        $newworkbook->rsstemplate = $workbook->singletemplate.' [[' . $newfieldname . ']]';
        $change = true;
    }
    if ($change) {
        $DB->update_record('workbook', $newworkbook);
    }
}


/**
 * given a field name
 * this function creates an instance of the particular subfield class
 *
 * @global object
 * @param string $name
 * @param object $workbook
 * @return object|bool
 */
function workbook_get_field_from_name($name, $workbook){
    global $DB;

    $field = $DB->get_record('workbook_fields', array('name'=>$name, 'workbookid'=>$workbook->id));

    if ($field) {
        return workbook_get_field($field, $workbook);
    } else {
        return false;
    }
}

/**
 * given a field id
 * this function creates an instance of the particular subfield class
 *
 * @global object
 * @param int $fieldid
 * @param object $workbook
 * @return bool|object
 */
function workbook_get_field_from_id($fieldid, $workbook){
    global $DB;

    $field = $DB->get_record('workbook_fields', array('id'=>$fieldid, 'workbookid'=>$workbook->id));

    if ($field) {
        return workbook_get_field($field, $workbook);
    } else {
        return false;
    }
}

/**
 * given a field id
 * this function creates an instance of the particular subfield class
 *
 * @global object
 * @param string $type
 * @param object $workbook
 * @return object
 */
function workbook_get_field_new($type, $workbook) {
    global $CFG;

    require_once($CFG->dirroot.'/mod/workbook/field/'.$type.'/field.class.php');
    $newfield = 'workbook_field_'.$type;
    $newfield = new $newfield(0, $workbook);
    return $newfield;
}

/**
 * returns a subclass field object given a record of the field, used to
 * invoke plugin methods
 * input: $param $field - record from db
 *
 * @global object
 * @param object $field
 * @param object $workbook
 * @param object $cm
 * @return object
 */
function workbook_get_field($field, $workbook, $cm=null) {
    global $CFG;

    if ($field) {
        require_once('field/'.$field->type.'/field.class.php');
        $newfield = 'workbook_field_'.$field->type;
        $newfield = new $newfield($field, $workbook, $cm);
        return $newfield;
    }
}


/**
 * Given record object (or id), returns true if the record belongs to the current user
 *
 * @global object
 * @global object
 * @param mixed $record record object or id
 * @return bool
 */
function workbook_isowner($record) {
    global $USER, $DB;

    if (!isloggedin()) { // perf shortcut
        return false;
    }

    if (!is_object($record)) {
        if (!$record = $DB->get_record('workbook_records', array('id'=>$record))) {
            return false;
        }
    }

    return ($record->userid == $USER->id);
}

/**
 * has a user reached the max number of entries?
 *
 * @param object $workbook
 * @return bool
 */
function workbook_atmaxentries($workbook){
    if (!$workbook->maxentries){
        return false;

    } else {
        return (workbook_numentries($workbook) >= $workbook->maxentries);
    }
}

/**
 * returns the number of entries already made by this user
 *
 * @global object
 * @global object
 * @param object $workbook
 * @return int
 */
function workbook_numentries($workbook){
    global $USER, $DB;
    $sql = 'SELECT COUNT(*) FROM {workbook_records} WHERE workbookid=? AND userid=?';
    return $DB->count_records_sql($sql, array($workbook->id, $USER->id));
}

/**
 * function that takes in a workbookid and adds a record
 * this is used everytime an add template is submitted
 *
 * @global object
 * @global object
 * @param object $workbook
 * @param int $groupid
 * @return bool
 */
function workbook_add_record($workbook, $groupid=0){
    global $USER, $DB;

    $cm = get_coursemodule_from_instance('workbook', $workbook->id);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $record = new stdClass();
    $record->userid = $USER->id;
    $record->workbookid = $workbook->id;
    $record->groupid = $groupid;
    $record->timecreated = $record->timemodified = time();
    if (has_capability('mod/workbook:approve', $context)) {
        $record->approved = 1;
    } else {
        $record->approved = 0;
    }
    return $DB->insert_record('workbook_records', $record);
}

/**
 * check the multple existence any tag in a template
 *
 * check to see if there are 2 or more of the same tag being used.
 *
 * @global object
 * @param int $workbookid,
 * @param string $template
 * @return bool
 */
function workbook_tags_check($workbookid, $template) {
    global $DB, $OUTPUT;

    // first get all the possible tags
    $fields = $DB->get_records('workbook_fields', array('workbookid'=>$workbookid));
    // then we generate strings to replace
    $tagsok = true; // let's be optimistic
    foreach ($fields as $field){
        $pattern="/\[\[".$field->name."\]\]/i";
        if (preg_match_all($pattern, $template, $dummy)>1){
            $tagsok = false;
            echo $OUTPUT->notification('[['.$field->name.']] - '.get_string('multipletags','workbook'));
        }
    }
    // else return true
    return $tagsok;
}

/**
 * Adds an instance of a workbook
 *
 * @global object
 * @param object $workbook
 * @return $int
 */
function workbook_add_instance($workbook) {
    global $DB;

    if (empty($workbook->assessed)) {
        $workbook->assessed = 0;
    }

    $workbook->timemodified = time();

    $workbook->id = $DB->insert_record('workbook', $workbook);

    workbook_grade_item_update($workbook);

    return $workbook->id;
}

/**
 * updates an instance of a workbook
 *
 * @global object
 * @param object $workbook
 * @return bool
 */
function workbook_update_instance($workbook) {
    global $DB, $OUTPUT;

    $workbook->timemodified = time();
    $workbook->id           = $workbook->instance;

    if (empty($workbook->assessed)) {
        $workbook->assessed = 0;
    }

    if (empty($workbook->ratingtime) or empty($workbook->assessed)) {
        $workbook->assesstimestart  = 0;
        $workbook->assesstimefinish = 0;
    }

    if (empty($workbook->notification)) {
        $workbook->notification = 0;
    }

    $DB->update_record('workbook', $workbook);

    workbook_grade_item_update($workbook);

    return true;

}

/**
 * deletes an instance of a workbook
 *
 * @global object
 * @param int $id
 * @return bool
 */
function workbook_delete_instance($id) {    // takes the workbookid
    global $DB, $CFG;

    if (!$workbook = $DB->get_record('workbook', array('id'=>$id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('workbook', $workbook->id);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

/// Delete all the associated information

    // files
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_workbook');

    // get all the records in this workbook
    $sql = "SELECT r.id
              FROM {workbook_records} r
             WHERE r.workbookid = ?";

    $DB->delete_records_select('workbook_content', "recordid IN ($sql)", array($id));

    // delete all the records and fields
    $DB->delete_records('workbook_records', array('workbookid'=>$id));
    $DB->delete_records('workbook_fields', array('workbookid'=>$id));

    // Delete the instance itself
    $result = $DB->delete_records('workbook', array('id'=>$id));

    // cleanup gradebook
    workbook_grade_item_delete($workbook);

    return $result;
}

/**
 * returns a summary of workbook activity of this user
 *
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $workbook
 * @return object|null
 */
function workbook_user_outline($course, $user, $mod, $workbook) {
    global $DB, $CFG;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'workbook', $workbook->id, $user->id);
    if (empty($grades->items[0]->grades)) {
        $grade = false;
    } else {
        $grade = reset($grades->items[0]->grades);
    }


    if ($countrecords = $DB->count_records('workbook_records', array('workbookid'=>$workbook->id, 'userid'=>$user->id))) {
        $result = new stdClass();
        $result->info = get_string('numrecords', 'workbook', $countrecords);
        $lastrecord   = $DB->get_record_sql('SELECT id,timemodified FROM {workbook_records}
                                              WHERE workbookid = ? AND userid = ?
                                           ORDER BY timemodified DESC', array($workbook->id, $user->id), true);
        $result->time = $lastrecord->timemodified;
        if ($grade) {
            $result->info .= ', ' . get_string('grade') . ': ' . $grade->str_long_grade;
        }
        return $result;
    } else if ($grade) {
        $result = new stdClass();
        $result->info = get_string('grade') . ': ' . $grade->str_long_grade;

        //datesubmitted == time created. dategraded == time modified or time overridden
        //if grade was last modified by the user themselves use date graded. Otherwise use date submitted
        //TODO: move this copied & pasted code somewhere in the grades API. See MDL-26704
        if ($grade->usermodified == $user->id || empty($grade->datesubmitted)) {
            $result->time = $grade->dategraded;
        } else {
            $result->time = $grade->datesubmitted;
        }

        return $result;
    }
    return NULL;
}

/**
 * Prints all the records uploaded by this user
 *
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $workbook
 */
function workbook_user_complete($course, $user, $mod, $workbook) {
    global $DB, $CFG, $OUTPUT;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'workbook', $workbook->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        echo $OUTPUT->container(get_string('grade').': '.$grade->str_long_grade);
        if ($grade->str_feedback) {
            echo $OUTPUT->container(get_string('feedback').': '.$grade->str_feedback);
        }
    }

    if ($records = $DB->get_records('workbook_records', array('workbookid'=>$workbook->id,'userid'=>$user->id), 'timemodified DESC')) {
        workbook_print_template('singletemplate', $records, $workbook);
    }
}

/**
 * Return grade for given user or all users.
 *
 * @global object
 * @param object $workbook
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function workbook_get_user_grades($workbook, $userid=0) {
    global $CFG;

    require_once($CFG->dirroot.'/rating/lib.php');

    $ratingoptions = new stdClass;
    $ratingoptions->component = 'mod_workbook';
    $ratingoptions->ratingarea = 'entry';
    $ratingoptions->modulename = 'workbook';
    $ratingoptions->moduleid   = $workbook->id;

    $ratingoptions->userid = $userid;
    $ratingoptions->aggregationmethod = $workbook->assessed;
    $ratingoptions->scaleid = $workbook->scale;
    $ratingoptions->itemtable = 'workbook_records';
    $ratingoptions->itemtableusercolumn = 'userid';

    $rm = new rating_manager();
    return $rm->get_user_grades($ratingoptions);
}

/**
 * Update activity grades
 *
 * @global object
 * @global object
 * @param object $workbook
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone
 */
function workbook_update_grades($workbook, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if (!$workbook->assessed) {
        workbook_grade_item_update($workbook);

    } else if ($grades = workbook_get_user_grades($workbook, $userid)) {
        workbook_grade_item_update($workbook, $grades);

    } else if ($userid and $nullifnone) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = NULL;
        workbook_grade_item_update($workbook, $grade);

    } else {
        workbook_grade_item_update($workbook);
    }
}

/**
 * Update all grades in gradebook.
 *
 * @global object
 */
function workbook_upgrade_grades() {
    global $DB;

    $sql = "SELECT COUNT('x')
              FROM {workbook} d, {course_modules} cm, {modules} m
             WHERE m.name='workbook' AND m.id=cm.module AND cm.instance=d.id";
    $count = $DB->count_records_sql($sql);

    $sql = "SELECT d.*, cm.idnumber AS cmidnumber, d.course AS courseid
              FROM {workbook} d, {course_modules} cm, {modules} m
             WHERE m.name='workbook' AND m.id=cm.module AND cm.instance=d.id";
    $rs = $DB->get_recordset_sql($sql);
    if ($rs->valid()) {
        // too much debug output
        $pbar = new progress_bar('workbookupgradegrades', 500, true);
        $i=0;
        foreach ($rs as $workbook) {
            $i++;
            upgrade_set_timeout(60*5); // set up timeout, may also abort execution
            workbook_update_grades($workbook, 0, false);
            $pbar->update($i, $count, "Updating Workbook grades ($i/$count).");
        }
    }
    $rs->close();
}

/**
 * Update/create grade item for given workbook
 *
 * @global object
 * @param object $workbook object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return object grade_item
 */
function workbook_grade_item_update($workbook, $grades=NULL) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $params = array('itemname'=>$workbook->name, 'idnumber'=>$workbook->cmidnumber);

    if (!$workbook->assessed or $workbook->scale == 0) {
        $params['gradetype'] = GRADE_TYPE_NONE;

    } else if ($workbook->scale > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $workbook->scale;
        $params['grademin']  = 0;

    } else if ($workbook->scale < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$workbook->scale;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

    return grade_update('mod/workbook', $workbook->course, 'mod', 'workbook', $workbook->id, 0, $grades, $params);
}

/**
 * Delete grade item for given workbook
 *
 * @global object
 * @param object $workbook object
 * @return object grade_item
 */
function workbook_grade_item_delete($workbook) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/workbook', $workbook->course, 'mod', 'workbook', $workbook->id, 0, NULL, array('deleted'=>1));
}

/**
 * returns a list of participants of this workbook
 *
 * Returns the users with workbook in one workbook
 * (users with records in workbook_records, workbook_comments and ratings)
 *
 * @todo: deprecated - to be deleted in 2.2
 *
 * @param int $workbookid
 * @return array
 */
function workbook_get_participants($workbookid) {
    global $DB;

    $params = array('workbookid' => $workbookid);

    $sql = "SELECT DISTINCT u.id, u.id
              FROM {user} u,
                   {workbook_records} r
             WHERE r.workbookid = :workbookid AND
                   u.id = r.userid";
    $records = $DB->get_records_sql($sql, $params);

    $sql = "SELECT DISTINCT u.id, u.id
              FROM {user} u,
                   {workbook_records} r,
                   {comments} c
             WHERE r.workbookid = ? AND
                   u.id = r.userid AND
                   r.id = c.itemid AND
                   c.commentarea = 'workbook_entry'";
    $comments = $DB->get_records_sql($sql, $params);

    $sql = "SELECT DISTINCT u.id, u.id
              FROM {user} u,
                   {workbook_records} r,
                   {ratings} a
             WHERE r.workbookid = ? AND
                   u.id = r.userid AND
                   r.id = a.itemid AND
                   a.component = 'mod_workbook' AND
                   a.ratingarea = 'entry'";
    $ratings = $DB->get_records_sql($sql, $params);

    $participants = array();

    if ($records) {
        foreach ($records as $record) {
            $participants[$record->id] = $record;
        }
    }
    if ($comments) {
        foreach ($comments as $comment) {
            $participants[$comment->id] = $comment;
        }
    }
    if ($ratings) {
        foreach ($ratings as $rating) {
            $participants[$rating->id] = $rating;
        }
    }

    return $participants;
}

// junk functions
/**
 * takes a list of records, the current workbook, a search string,
 * and mode to display prints the translated template
 *
 * @global object
 * @global object
 * @param string $template
 * @param array $records
 * @param object $workbook
 * @param string $search
 * @param int $page
 * @param bool $return
 * @return mixed
 */
function workbook_print_template($template, $records, $workbook, $search='', $page=0, $return=false) {
    global $CFG, $DB, $OUTPUT;
    $cm = get_coursemodule_from_instance('workbook', $workbook->id);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    static $fields = NULL;
    static $isteacher;
    static $workbookid = NULL;

    if (empty($workbookid)) {
        $workbookid = $workbook->id;
    } else if ($workbookid != $workbook->id) {
        $fields = NULL;
    }

    if (empty($fields)) {
        $fieldrecords = $DB->get_records('workbook_fields', array('workbookid'=>$workbook->id));
        foreach ($fieldrecords as $fieldrecord) {
            $fields[]= workbook_get_field($fieldrecord, $workbook);
        }
        $isteacher = has_capability('mod/workbook:managetemplates', $context);
    }

    if (empty($records)) {
        return;
    }

    foreach ($records as $record) {   // Might be just one for the single template

    // Replacing tags
        $patterns = array();
        $replacement = array();

    // Then we generate strings to replace for normal tags
        foreach ($fields as $field) {
            $patterns[]='[['.$field->field->name.']]';
            $replacement[] = highlight($search, $field->display_browse_field($record->id, $template));
        }

    // Replacing special tags (##Edit##, ##Delete##, ##More##)
        $patterns[]='##edit##';
        $patterns[]='##delete##';
        if (has_capability('mod/workbook:manageentries', $context) or workbook_isowner($record->id)) {
            $replacement[] = '<a href="'.$CFG->wwwroot.'/mod/workbook/edit.php?d='
                             .$workbook->id.'&amp;rid='.$record->id.'&amp;sesskey='.sesskey().'"><img src="'.$OUTPUT->pix_url('t/edit') . '" class="iconsmall" alt="'.get_string('edit').'" title="'.get_string('edit').'" /></a>';
            $replacement[] = '<a href="'.$CFG->wwwroot.'/mod/workbook/view.php?d='
                             .$workbook->id.'&amp;delete='.$record->id.'&amp;sesskey='.sesskey().'"><img src="'.$OUTPUT->pix_url('t/delete') . '" class="iconsmall" alt="'.get_string('delete').'" title="'.get_string('delete').'" /></a>';
        } else {
            $replacement[] = '';
            $replacement[] = '';
        }

        $moreurl = $CFG->wwwroot . '/mod/workbook/view.php?d=' . $workbook->id . '&amp;rid=' . $record->id;
        if ($search) {
            $moreurl .= '&amp;filter=1';
        }
        $patterns[]='##more##';
        $replacement[] = '<a href="' . $moreurl . '"><img src="' . $OUTPUT->pix_url('i/search') . '" class="iconsmall" alt="' . get_string('more', 'workbook') . '" title="' . get_string('more', 'workbook') . '" /></a>';

        $patterns[]='##moreurl##';
        $replacement[] = $moreurl;

        $patterns[]='##user##';
        $replacement[] = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$record->userid.
                               '&amp;course='.$workbook->course.'">'.fullname($record).'</a>';

        $patterns[]='##export##';

        if (!empty($CFG->enableportfolios) && ($template == 'singletemplate' || $template == 'listtemplate')
            && ((has_capability('mod/workbook:exportentry', $context)
                || (workbook_isowner($record->id) && has_capability('mod/workbook:exportownentry', $context))))) {
            require_once($CFG->libdir . '/portfoliolib.php');
            $button = new portfolio_add_button();
            $button->set_callback_options('workbook_portfolio_caller', array('id' => $cm->id, 'recordid' => $record->id), '/mod/workbook/locallib.php');
            list($formats, $files) = workbook_portfolio_caller::formats($fields, $record);
            $button->set_formats($formats);
            $replacement[] = $button->to_html(PORTFOLIO_ADD_ICON_LINK);
        } else {
            $replacement[] = '';
        }

        $patterns[] = '##timeadded##';
        $replacement[] = userdate($record->timecreated);

        $patterns[] = '##timemodified##';
        $replacement [] = userdate($record->timemodified);

        $patterns[]='##approve##';
        if (has_capability('mod/workbook:approve', $context) && ($workbook->approval) && (!$record->approved)){
            $replacement[] = '<span class="approve"><a href="'.$CFG->wwwroot.'/mod/workbook/view.php?d='.$workbook->id.'&amp;approve='.$record->id.'&amp;sesskey='.sesskey().'"><img src="'.$OUTPUT->pix_url('i/approve') . '" class="icon" alt="'.get_string('approve').'" /></a></span>';
        } else {
            $replacement[] = '';
        }

        $patterns[]='##comments##';
        if (($template == 'listtemplate') && ($workbook->comments)) {

            if (!empty($CFG->usecomments)) {
                require_once($CFG->dirroot  . '/comment/lib.php');
                list($context, $course, $cm) = get_context_info_array($context->id);
                $cmt = new stdClass();
                $cmt->context = $context;
                $cmt->course  = $course;
                $cmt->cm      = $cm;
                $cmt->area    = 'workbook_entry';
                $cmt->itemid  = $record->id;
                $cmt->showcount = true;
                $cmt->component = 'mod_workbook';
                $comment = new comment($cmt);
                $replacement[] = $comment->output(true);
            }
        } else {
            $replacement[] = '';
        }

        // actual replacement of the tags
        $newtext = str_ireplace($patterns, $replacement, $workbook->{$template});

        // no more html formatting and filtering - see MDL-6635
        if ($return) {
            return $newtext;
        } else {
            echo $newtext;

            // hack alert - return is always false in singletemplate anyway ;-)
            /**********************************
             *    Printing Ratings Form       *
             *********************************/
            if ($template == 'singletemplate') {    //prints ratings options
                workbook_print_ratings($workbook, $record);
            }

            /**********************************
             *    Printing Comments Form       *
             *********************************/
            if (($template == 'singletemplate') && ($workbook->comments)) {
                if (!empty($CFG->usecomments)) {
                    require_once($CFG->dirroot . '/comment/lib.php');
                    list($context, $course, $cm) = get_context_info_array($context->id);
                    $cmt = new stdClass();
                    $cmt->context = $context;
                    $cmt->course  = $course;
                    $cmt->cm      = $cm;
                    $cmt->area    = 'workbook_entry';
                    $cmt->itemid  = $record->id;
                    $cmt->showcount = true;
                    $cmt->component = 'mod_workbook';
                    $comment = new comment($cmt);
                    $comment->output(false);
                }
            }
        }
    }
}

/**
 * Return rating related permissions
 *
 * @param string $contextid the context id
 * @param string $component the component to get rating permissions for
 * @param string $ratingarea the rating area to get permissions for
 * @return array an associative array of the user's rating permissions
 */
function workbook_rating_permissions($contextid, $component, $ratingarea) {
    $context = get_context_instance_by_id($contextid, MUST_EXIST);
    if ($component != 'mod_workbook' || $ratingarea != 'entry') {
        return null;
    }
    return array(
        'view'    => has_capability('mod/workbook:viewrating',$context),
        'viewany' => has_capability('mod/workbook:viewanyrating',$context),
        'viewall' => has_capability('mod/workbook:viewallratings',$context),
        'rate'    => has_capability('mod/workbook:rate',$context)
    );
}

/**
 * Validates a submitted rating
 * @param array $params submitted workbook
 *            context => object the context in which the rated items exists [required]
 *            itemid => int the ID of the object being rated
 *            scaleid => int the scale from which the user can select a rating. Used for bounds checking. [required]
 *            rating => int the submitted rating
 *            rateduserid => int the id of the user whose items have been rated. NOT the user who submitted the ratings. 0 to update all. [required]
 *            aggregation => int the aggregation method to apply when calculating grades ie RATING_AGGREGATE_AVERAGE [required]
 * @return boolean true if the rating is valid. Will throw rating_exception if not
 */
function workbook_rating_validate($params) {
    global $DB, $USER;

    // Check the component is mod_workbook
    if ($params['component'] != 'mod_workbook') {
        throw new rating_exception('invalidcomponent');
    }

    // Check the ratingarea is entry (the only rating area in workbook module)
    if ($params['ratingarea'] != 'entry') {
        throw new rating_exception('invalidratingarea');
    }

    // Check the rateduserid is not the current user .. you can't rate your own entries
    if ($params['rateduserid'] == $USER->id) {
        throw new rating_exception('nopermissiontorate');
    }

    $workbooksql = "SELECT d.id as workbookid, d.scale, d.course, r.userid as userid, d.approval, r.approved, r.timecreated, d.assesstimestart, d.assesstimefinish, r.groupid
                  FROM {workbook_records} r
                  JOIN {workbook} d ON r.workbookid = d.id
                 WHERE r.id = :itemid";
    $workbookparams = array('itemid'=>$params['itemid']);
    if (!$info = $DB->get_record_sql($workbooksql, $workbookparams)) {
        //item doesn't exist
        throw new rating_exception('invaliditemid');
    }

    if ($info->scale != $params['scaleid']) {
        //the scale being submitted doesnt match the one in the workbook
        throw new rating_exception('invalidscaleid');
    }

    //check that the submitted rating is valid for the scale

    // lower limit
    if ($params['rating'] < 0  && $params['rating'] != RATING_UNSET_RATING) {
        throw new rating_exception('invalidnum');
    }

    // upper limit
    if ($info->scale < 0) {
        //its a custom scale
        $scalerecord = $DB->get_record('scale', array('id' => -$info->scale));
        if ($scalerecord) {
            $scalearray = explode(',', $scalerecord->scale);
            if ($params['rating'] > count($scalearray)) {
                throw new rating_exception('invalidnum');
            }
        } else {
            throw new rating_exception('invalidscaleid');
        }
    } else if ($params['rating'] > $info->scale) {
        //if its numeric and submitted rating is above maximum
        throw new rating_exception('invalidnum');
    }

    if ($info->approval && !$info->approved) {
        //workbook requires approval but this item isnt approved
        throw new rating_exception('nopermissiontorate');
    }

    // check the item we're rating was created in the assessable time window
    if (!empty($info->assesstimestart) && !empty($info->assesstimefinish)) {
        if ($info->timecreated < $info->assesstimestart || $info->timecreated > $info->assesstimefinish) {
            throw new rating_exception('notavailable');
        }
    }

    $course = $DB->get_record('course', array('id'=>$info->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('workbook', $info->workbookid, $course->id, false, MUST_EXIST);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id, MUST_EXIST);

    // if the supplied context doesnt match the item's context
    if ($context->id != $params['context']->id) {
        throw new rating_exception('invalidcontext');
    }

    // Make sure groups allow this user to see the item they're rating
    $groupid = $info->groupid;
    if ($groupid > 0 and $groupmode = groups_get_activity_groupmode($cm, $course)) {   // Groups are being used
        if (!groups_group_exists($groupid)) { // Can't find group
            throw new rating_exception('cannotfindgroup');//something is wrong
        }

        if (!groups_is_member($groupid) and !has_capability('moodle/site:accessallgroups', $context)) {
            // do not allow rating of posts from other groups when in SEPARATEGROUPS or VISIBLEGROUPS
            throw new rating_exception('notmemberofgroup');
        }
    }

    return true;
}


/**
 * function that takes in the current workbook, number of items per page,
 * a search string and prints a preference box in view.php
 *
 * This preference box prints a searchable advanced search template if
 *     a) A template is defined
 *  b) The advanced search checkbox is checked.
 *
 * @global object
 * @global object
 * @param object $workbook
 * @param int $perpage
 * @param string $search
 * @param string $sort
 * @param string $order
 * @param array $search_array
 * @param int $advanced
 * @param string $mode
 * @return void
 */
function workbook_print_preference_form($workbook, $perpage, $search, $sort='', $order='ASC', $search_array = '', $advanced = 0, $mode= ''){
    global $CFG, $DB, $PAGE, $OUTPUT, $cm;

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    if (!has_capability('mod/workbook:managetemplates', $context)) {
        return "";
    }

    $cm = get_coursemodule_from_instance('workbook', $workbook->id);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    echo '<br /><div class="workbookpreferences">';
    echo '<form id="options" action="view.php" method="get">';
    echo '<div>';
    echo '<input type="hidden" name="d" value="'.$workbook->id.'" />';
    if ($mode =='asearch') {
        $advanced = 1;
        echo '<input type="hidden" name="mode" value="list" />';
    }
    echo '<label for="pref_perpage">'.get_string('pagesize','workbook').'</label> ';
    $pagesizes = array(2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,15=>15,
                       20=>20,30=>30,40=>40,50=>50,100=>100,200=>200,300=>300,400=>400,500=>500,1000=>1000);
    echo html_writer::select($pagesizes, 'perpage', $perpage, false, array('id'=>'pref_perpage'));
    echo '<div id="reg_search" style="display: ';
    if ($advanced) {
        echo 'none';
    }
    else {
        echo 'inline';
    }
    echo ';" >&nbsp;&nbsp;&nbsp;<label for="pref_search">'.get_string('search').'</label> <input type="text" size="16" name="search" id= "pref_search" value="'.s($search).'" /></div>';
    echo '&nbsp;&nbsp;&nbsp;<label for="pref_sortby">'.get_string('sortby').'</label> ';
    // foreach field, print the option
    echo '<select name="sort" id="pref_sortby">';
    if ($fields = $DB->get_records('workbook_fields', array('workbookid'=>$workbook->id), 'name')) {
        echo '<optgroup label="'.get_string('fields', 'workbook').'">';
        foreach ($fields as $field) {
            if ($field->id == $sort) {
                echo '<option value="'.$field->id.'" selected="selected">'.$field->name.'</option>';
            } else {
                echo '<option value="'.$field->id.'">'.$field->name.'</option>';
            }
        }
        echo '</optgroup>';
    }
    $options = array();
    $options[WORKBOOK_TIMEADDED]    = get_string('timeadded', 'workbook');
    $options[WORKBOOK_TIMEMODIFIED] = get_string('timemodified', 'workbook');
    $options[WORKBOOK_FIRSTNAME]    = get_string('authorfirstname', 'workbook');
    $options[WORKBOOK_LASTNAME]     = get_string('authorlastname', 'workbook');
    if ($workbook->approval and has_capability('mod/workbook:approve', $context)) {
        $options[WORKBOOK_APPROVED] = get_string('approved', 'workbook');
    }
    echo '<optgroup label="'.get_string('other', 'workbook').'">';
    foreach ($options as $key => $name) {
        if ($key == $sort) {
            echo '<option value="'.$key.'" selected="selected">'.$name.'</option>';
        } else {
            echo '<option value="'.$key.'">'.$name.'</option>';
        }
    }
    echo '</optgroup>';
    echo '</select>';
    echo '<label for="pref_order" class="accesshide">'.get_string('order').'</label>';
    echo '<select id="pref_order" name="order">';
    if ($order == 'ASC') {
        echo '<option value="ASC" selected="selected">'.get_string('ascending','workbook').'</option>';
    } else {
        echo '<option value="ASC">'.get_string('ascending','workbook').'</option>';
    }
    if ($order == 'DESC') {
        echo '<option value="DESC" selected="selected">'.get_string('descending','workbook').'</option>';
    } else {
        echo '<option value="DESC">'.get_string('descending','workbook').'</option>';
    }
    echo '</select>';

    if ($advanced) {
        $checked = ' checked="checked" ';
    }
    else {
        $checked = '';
    }
    $PAGE->requires->js('/mod/workbook/workbook.js');
    echo '&nbsp;<input type="hidden" name="advanced" value="0" />';
    echo '&nbsp;<input type="hidden" name="filter" value="1" />';
    echo '&nbsp;<input type="checkbox" id="advancedcheckbox" name="advanced" value="1" '.$checked.' onchange="showHideAdvSearch(this.checked);" /><label for="advancedcheckbox">'.get_string('advancedsearch', 'workbook').'</label>';
    echo '&nbsp;<input type="submit" value="'.get_string('savesettings','workbook').'" />';

    echo '<br />';
    echo '<div class="workbookadvancedsearch" id="workbook_adv_form" style="display: ';

    if ($advanced) {
        echo 'inline';
    }
    else {
        echo 'none';
    }
    echo ';margin-left:auto;margin-right:auto;" >';
    echo '<table class="boxaligncenter">';

    // print ASC or DESC
    echo '<tr><td colspan="2">&nbsp;</td></tr>';
    $i = 0;

    // Determine if we are printing all fields for advanced search, or the template for advanced search
    // If a template is not defined, use the deafault template and display all fields.
    if(empty($workbook->asearchtemplate)) {
        workbook_generate_default_template($workbook, 'asearchtemplate');
    }

    static $fields = NULL;
    static $isteacher;
    static $workbookid = NULL;

    if (empty($workbookid)) {
        $workbookid = $workbook->id;
    } else if ($workbookid != $workbook->id) {
        $fields = NULL;
    }

    if (empty($fields)) {
        $fieldrecords = $DB->get_records('workbook_fields', array('workbookid'=>$workbook->id));
        foreach ($fieldrecords as $fieldrecord) {
            $fields[]= workbook_get_field($fieldrecord, $workbook);
        }

        $isteacher = has_capability('mod/workbook:managetemplates', $context);
    }

    // Replacing tags
    $patterns = array();
    $replacement = array();

    // Then we generate strings to replace for normal tags
    foreach ($fields as $field) {
        $fieldname = $field->field->name;
        $fieldname = preg_quote($fieldname, '/');
        $patterns[] = "/\[\[$fieldname\]\]/i";
        $searchfield = workbook_get_field_from_id($field->field->id, $workbook);
        if (!empty($search_array[$field->field->id]->workbook)) {
            $replacement[] = $searchfield->display_search_field($search_array[$field->field->id]->workbook);
        } else {
            $replacement[] = $searchfield->display_search_field();
        }
    }
    $fn = !empty($search_array[WORKBOOK_FIRSTNAME]->workbook) ? $search_array[WORKBOOK_FIRSTNAME]->workbook : '';
    $ln = !empty($search_array[WORKBOOK_LASTNAME]->workbook) ? $search_array[WORKBOOK_LASTNAME]->workbook : '';
    $patterns[]    = '/##firstname##/';
    $replacement[] = '<input type="text" size="16" name="u_fn" value="'.$fn.'" />';
    $patterns[]    = '/##lastname##/';
    $replacement[] = '<input type="text" size="16" name="u_ln" value="'.$ln.'" />';

    // actual replacement of the tags
    $newtext = preg_replace($patterns, $replacement, $workbook->asearchtemplate);

    $options = new stdClass();
    $options->para=false;
    $options->noclean=true;
    echo '<tr><td>';
    echo format_text($newtext, FORMAT_HTML, $options);
    echo '</td></tr>';

    echo '<tr><td colspan="4" style="text-align: center;"><br/><input type="submit" value="'.get_string('savesettings','workbook').'" /><input type="submit" name="resetadv" value="'.get_string('resetsettings','workbook').'" /></td></tr>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    echo '</form>';
    echo '</div>';
}

/**
 * @global object
 * @global object
 * @param object $workbook
 * @param object $record
 * @return void Output echo'd
 */
function workbook_print_ratings($workbook, $record) {
    global $OUTPUT;
    if (!empty($record->rating)){
        echo $OUTPUT->render($record->rating);
    }
}

/**
 * For Participantion Reports
 *
 * @return array
 */
function workbook_get_view_actions() {
    return array('view');
}

/**
 * @return array
 */
function workbook_get_post_actions() {
    return array('add','update','record delete');
}

/**
 * @param string $name
 * @param int $workbookid
 * @param int $fieldid
 * @return bool
 */
function workbook_fieldname_exists($name, $workbookid, $fieldid = 0) {
    global $DB;

    if (!is_numeric($name)) {
        $like = $DB->sql_like('df.name', ':name', false);
    } else {
        $like = "df.name = :name";
    }
    $params = array('name'=>$name);
    if ($fieldid) {
        $params['workbookid']   = $workbookid;
        $params['fieldid1'] = $fieldid;
        $params['fieldid2'] = $fieldid;
        return $DB->record_exists_sql("SELECT * FROM {workbook_fields} df
                                        WHERE $like AND df.workbookid = :workbookid
                                              AND ((df.id < :fieldid1) OR (df.id > :fieldid2))", $params);
    } else {
        $params['workbookid']   = $workbookid;
        return $DB->record_exists_sql("SELECT * FROM {workbook_fields} df
                                        WHERE $like AND df.workbookid = :workbookid", $params);
    }
}

/**
 * @param array $fieldinput
 */
function workbook_convert_arrays_to_strings(&$fieldinput) {
    foreach ($fieldinput as $key => $val) {
        if (is_array($val)) {
            $str = '';
            foreach ($val as $inner) {
                $str .= $inner . ',';
            }
            $str = substr($str, 0, -1);

            $fieldinput->$key = $str;
        }
    }
}


/**
 * Converts a workbook (module instance) to use the Roles System
 *
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @uses CAP_PREVENT
 * @uses CAP_ALLOW
 * @param object $workbook a workbook object with the same attributes as a record
 *                     from the workbook workbook table
 * @param int $workbookmodid the id of the workbook module, from the modules table
 * @param array $teacherroles array of roles that have archetype teacher
 * @param array $studentroles array of roles that have archetype student
 * @param array $guestroles array of roles that have archetype guest
 * @param int $cmid the course_module id for this workbook instance
 * @return boolean workbook module was converted or not
 */
function workbook_convert_to_roles($workbook, $teacherroles=array(), $studentroles=array(), $cmid=NULL) {
    global $CFG, $DB, $OUTPUT;

    if (!isset($workbook->participants) && !isset($workbook->assesspublic)
            && !isset($workbook->groupmode)) {
        // We assume that this workbook has already been converted to use the
        // Roles System. above fields get dropped the workbook module has been
        // upgraded to use Roles.
        return false;
    }

    if (empty($cmid)) {
        // We were not given the course_module id. Try to find it.
        if (!$cm = get_coursemodule_from_instance('workbook', $workbook->id)) {
            echo $OUTPUT->notification('Could not get the course module for the workbook');
            return false;
        } else {
            $cmid = $cm->id;
        }
    }
    $context = get_context_instance(CONTEXT_MODULE, $cmid);


    // $workbook->participants:
    // 1 - Only teachers can add entries
    // 3 - Teachers and students can add entries
    switch ($workbook->participants) {
        case 1:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/workbook:writeentry', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/workbook:writeentry', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
        case 3:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/workbook:writeentry', CAP_ALLOW, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/workbook:writeentry', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
    }

    // $workbook->assessed:
    // 2 - Only teachers can rate posts
    // 1 - Everyone can rate posts
    // 0 - No one can rate posts
    switch ($workbook->assessed) {
        case 0:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/workbook:rate', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/workbook:rate', CAP_PREVENT, $teacherrole->id, $context->id);
            }
            break;
        case 1:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/workbook:rate', CAP_ALLOW, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/workbook:rate', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
        case 2:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/workbook:rate', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/workbook:rate', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
    }

    // $workbook->assesspublic:
    // 0 - Students can only see their own ratings
    // 1 - Students can see everyone's ratings
    switch ($workbook->assesspublic) {
        case 0:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/workbook:viewrating', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/workbook:viewrating', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
        case 1:
            foreach ($studentroles as $studentrole) {
                assign_capability('mod/workbook:viewrating', CAP_ALLOW, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('mod/workbook:viewrating', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
    }

    if (empty($cm)) {
        $cm = $DB->get_record('course_modules', array('id'=>$cmid));
    }

    switch ($cm->groupmode) {
        case NOGROUPS:
            break;
        case SEPARATEGROUPS:
            foreach ($studentroles as $studentrole) {
                assign_capability('moodle/site:accessallgroups', CAP_PREVENT, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
        case VISIBLEGROUPS:
            foreach ($studentroles as $studentrole) {
                assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $studentrole->id, $context->id);
            }
            foreach ($teacherroles as $teacherrole) {
                assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $teacherrole->id, $context->id);
            }
            break;
    }
    return true;
}

/**
 * Returns the best name to show for a preset
 *
 * @param string $shortname
 * @param  string $path
 * @return string
 */
function workbook_preset_name($shortname, $path) {

    // We are looking inside the preset itself as a first choice, but also in normal workbook directory
    $string = get_string('modulename', 'workbookpreset_'.$shortname);

    if (substr($string, 0, 1) == '[') {
        return $shortname;
    } else {
        return $string;
    }
}

/**
 * Returns an array of all the available presets.
 *
 * @return array
 */
function workbook_get_available_presets($context) {
    global $CFG, $USER;

    $presets = array();

    // First load the ratings sub plugins that exist within the modules preset dir
    if ($dirs = get_list_of_plugins('mod/workbook/preset')) {
        foreach ($dirs as $dir) {
            $fulldir = $CFG->dirroot.'/mod/workbook/preset/'.$dir;
            if (workbook_is_directory_a_preset($fulldir)) {
                $preset = new stdClass();
                $preset->path = $fulldir;
                $preset->userid = 0;
                $preset->shortname = $dir;
                $preset->name = workbook_preset_name($dir, $fulldir);
                if (file_exists($fulldir.'/screenshot.jpg')) {
                    $preset->screenshot = $CFG->wwwroot.'/mod/workbook/preset/'.$dir.'/screenshot.jpg';
                } else if (file_exists($fulldir.'/screenshot.png')) {
                    $preset->screenshot = $CFG->wwwroot.'/mod/workbook/preset/'.$dir.'/screenshot.png';
                } else if (file_exists($fulldir.'/screenshot.gif')) {
                    $preset->screenshot = $CFG->wwwroot.'/mod/workbook/preset/'.$dir.'/screenshot.gif';
                }
                $presets[] = $preset;
            }
        }
    }
    // Now add to that the site presets that people have saved
    $presets = workbook_get_available_site_presets($context, $presets);
    return $presets;
}

/**
 * Gets an array of all of the presets that users have saved to the site.
 *
 * @param stdClass $context The context that we are looking from.
 * @param array $presets
 * @return array An array of presets
 */
function workbook_get_available_site_presets($context, array $presets=array()) {
    global $USER;

    $fs = get_file_storage();
    $files = $fs->get_area_files(WORKBOOK_PRESET_CONTEXT, WORKBOOK_PRESET_COMPONENT, WORKBOOK_PRESET_FILEAREA);
    $canviewall = has_capability('mod/workbook:viewalluserpresets', $context);
    if (empty($files)) {
        return $presets;
    }
    foreach ($files as $file) {
        if (($file->is_directory() && $file->get_filepath()=='/') || !$file->is_directory() || (!$canviewall && $file->get_userid() != $USER->id)) {
            continue;
        }
        $preset = new stdClass;
        $preset->path = $file->get_filepath();
        $preset->name = trim($preset->path, '/');
        $preset->shortname = $preset->name;
        $preset->userid = $file->get_userid();
        $preset->id = $file->get_id();
        $preset->storedfile = $file;
        $presets[] = $preset;
    }
    return $presets;
}

/**
 * Deletes a saved preset.
 *
 * @param string $name
 * @return bool
 */
function workbook_delete_site_preset($name) {
    $fs = get_file_storage();

    $files = $fs->get_directory_files(WORKBOOK_PRESET_CONTEXT, WORKBOOK_PRESET_COMPONENT, WORKBOOK_PRESET_FILEAREA, 0, '/'.$name.'/');
    if (!empty($files)) {
        foreach ($files as $file) {
            $file->delete();
        }
    }

    $dir = $fs->get_file(WORKBOOK_PRESET_CONTEXT, WORKBOOK_PRESET_COMPONENT, WORKBOOK_PRESET_FILEAREA, 0, '/'.$name.'/', '.');
    if (!empty($dir)) {
        $dir->delete();
    }
    return true;
}

/**
 * Prints the heads for a page
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $workbook
 * @param string $currenttab
 */
function workbook_print_header($course, $cm, $workbook, $currenttab='') {

    global $CFG, $displaynoticegood, $displaynoticebad, $OUTPUT, $PAGE;

    $PAGE->set_title($workbook->name);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($workbook->name));

// Groups needed for Add entry tab
    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);

    // Print the tabs

    if ($currenttab) {
        include('tabs.php');
    }

    // Print any notices

    if (!empty($displaynoticegood)) {
        echo $OUTPUT->notification($displaynoticegood, 'notifysuccess');    // good (usually green)
    } else if (!empty($displaynoticebad)) {
        echo $OUTPUT->notification($displaynoticebad);                     // bad (usuually red)
    }
}

/**
 * Can user add more entries?
 *
 * @param object $workbook
 * @param mixed $currentgroup
 * @param int $groupmode
 * @param stdClass $context
 * @return bool
 */
function workbook_user_can_add_entry($workbook, $currentgroup, $groupmode, $context = null) {
    global $USER;

    if (empty($context)) {
        $cm = get_coursemodule_from_instance('workbook', $workbook->id, 0, false, MUST_EXIST);
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    }

    if (has_capability('mod/workbook:manageentries', $context)) {
        // no entry limits apply if user can manage

    } else if (!has_capability('mod/workbook:writeentry', $context)) {
        return false;

    } else if (workbook_atmaxentries($workbook)) {
        return false;
    }

    //if in the view only time window
    $now = time();
    if ($now>$workbook->timeviewfrom && $now<$workbook->timeviewto) {
        return false;
    }

    if (!$groupmode or has_capability('moodle/site:accessallgroups', $context)) {
        return true;
    }

    if ($currentgroup) {
        return groups_is_member($currentgroup);
    } else {
        //else it might be group 0 in visible mode
        if ($groupmode == VISIBLEGROUPS){
            return true;
        } else {
            return false;
        }
    }
}


/**
 * @return bool
 */
function workbook_is_directory_a_preset($directory) {
    $directory = rtrim($directory, '/\\') . '/';
    $status = file_exists($directory.'singletemplate.html') &&
              file_exists($directory.'listtemplate.html') &&
              file_exists($directory.'listtemplateheader.html') &&
              file_exists($directory.'listtemplatefooter.html') &&
              file_exists($directory.'addtemplate.html') &&
              file_exists($directory.'rsstemplate.html') &&
              file_exists($directory.'rsstitletemplate.html') &&
              file_exists($directory.'csstemplate.css') &&
              file_exists($directory.'jstemplate.js') &&
              file_exists($directory.'preset.xml');

    return $status;
}

/**
 * Abstract class used for workbook preset importers
 */
abstract class workbook_preset_importer {

    protected $course;
    protected $cm;
    protected $module;
    protected $directory;

    /**
     * Constructor
     *
     * @param stdClass $course
     * @param stdClass $cm
     * @param stdClass $module
     * @param string $directory
     */
    public function __construct($course, $cm, $module, $directory) {
        $this->course = $course;
        $this->cm = $cm;
        $this->module = $module;
        $this->directory = $directory;
    }

    /**
     * Returns the name of the directory the preset is located in
     * @return string
     */
    public function get_directory() {
        return basename($this->directory);
    }

    /**
     * Retreive the contents of a file. That file may either be in a conventional directory of the Moodle file storage
     * @param file_storage $filestorage. should be null if using a conventional directory
     * @param stored_file $fileobj the directory to look in. null if using a conventional directory
     * @param string $dir the directory to look in. null if using the Moodle file storage
     * @param string $filename the name of the file we want
     * @return string the contents of the file
     */
    public function workbook_preset_get_file_contents(&$filestorage, &$fileobj, $dir, $filename) {
        if(empty($filestorage) || empty($fileobj)) {
            if (substr($dir, -1)!='/') {
                $dir .= '/';
            }
            return file_get_contents($dir.$filename);
        } else {
            $file = $filestorage->get_file(WORKBOOK_PRESET_CONTEXT, WORKBOOK_PRESET_COMPONENT, WORKBOOK_PRESET_FILEAREA, 0, $fileobj->get_filepath(), $filename);
            return $file->get_content();
        }

    }
    /**
     * Gets the preset settings
     * @global moodle_workbook $DB
     * @return stdClass
     */
    public function get_preset_settings() {
        global $DB;

        $fs = $fileobj = null;
        if (!workbook_is_directory_a_preset($this->directory)) {
            //maybe the user requested a preset stored in the Moodle file storage

            $fs = get_file_storage();
            $files = $fs->get_area_files(WORKBOOK_PRESET_CONTEXT, WORKBOOK_PRESET_COMPONENT, WORKBOOK_PRESET_FILEAREA);

            //preset name to find will be the final element of the directory
            $presettofind = end(explode('/',$this->directory));

            //now go through the available files available and see if we can find it
            foreach ($files as $file) {
                if (($file->is_directory() && $file->get_filepath()=='/') || !$file->is_directory()) {
                    continue;
                }
                $presetname = trim($file->get_filepath(), '/');
                if ($presetname==$presettofind) {
                    $this->directory = $presetname;
                    $fileobj = $file;
                }
            }

            if (empty($fileobj)) {
                print_error('invalidpreset', 'workbook', '', $this->directory);
            }
        }

        $allowed_settings = array(
            'intro',
            'comments',
            'requiredentries',
            'requiredentriestoview',
            'maxentries',
            'rssarticles',
            'approval',
            'defaultsortdir',
            'defaultsort');

        $result = new stdClass;
        $result->settings = new stdClass;
        $result->importfields = array();
        $result->currentfields = $DB->get_records('workbook_fields', array('workbookid'=>$this->module->id));
        if (!$result->currentfields) {
            $result->currentfields = array();
        }


        /* Grab XML */
        $presetxml = $this->workbook_preset_get_file_contents($fs, $fileobj, $this->directory,'preset.xml');
        $parsedxml = xmlize($presetxml, 0);

        /* First, do settings. Put in user friendly array. */
        $settingsarray = $parsedxml['preset']['#']['settings'][0]['#'];
        $result->settings = new StdClass();
        foreach ($settingsarray as $setting => $value) {
            if (!is_array($value) || !in_array($setting, $allowed_settings)) {
                // unsupported setting
                continue;
            }
            $result->settings->$setting = $value[0]['#'];
        }

        /* Now work out fields to user friendly array */
        $fieldsarray = $parsedxml['preset']['#']['field'];
        foreach ($fieldsarray as $field) {
            if (!is_array($field)) {
                continue;
            }
            $f = new StdClass();
            foreach ($field['#'] as $param => $value) {
                if (!is_array($value)) {
                    continue;
                }
                $f->$param = $value[0]['#'];
            }
            $f->workbookid = $this->module->id;
            $f->type = clean_param($f->type, PARAM_ALPHA);
            $result->importfields[] = $f;
        }
        /* Now add the HTML templates to the settings array so we can update d */
        $result->settings->singletemplate     = $this->workbook_preset_get_file_contents($fs, $fileobj,$this->directory,"singletemplate.html");
        $result->settings->listtemplate       = $this->workbook_preset_get_file_contents($fs, $fileobj,$this->directory,"listtemplate.html");
        $result->settings->listtemplateheader = $this->workbook_preset_get_file_contents($fs, $fileobj,$this->directory,"listtemplateheader.html");
        $result->settings->listtemplatefooter = $this->workbook_preset_get_file_contents($fs, $fileobj,$this->directory,"listtemplatefooter.html");
        $result->settings->addtemplate        = $this->workbook_preset_get_file_contents($fs, $fileobj,$this->directory,"addtemplate.html");
        $result->settings->rsstemplate        = $this->workbook_preset_get_file_contents($fs, $fileobj,$this->directory,"rsstemplate.html");
        $result->settings->rsstitletemplate   = $this->workbook_preset_get_file_contents($fs, $fileobj,$this->directory,"rsstitletemplate.html");
        $result->settings->csstemplate        = $this->workbook_preset_get_file_contents($fs, $fileobj,$this->directory,"csstemplate.css");
        $result->settings->jstemplate         = $this->workbook_preset_get_file_contents($fs, $fileobj,$this->directory,"jstemplate.js");

        //optional
        if (file_exists($this->directory."/asearchtemplate.html")) {
            $result->settings->asearchtemplate = $this->workbook_preset_get_file_contents($fs, $fileobj,$this->directory,"asearchtemplate.html");
        } else {
            $result->settings->asearchtemplate = NULL;
        }
        $result->settings->instance = $this->module->id;

        return $result;
    }

    /**
     * Import the preset into the given workbook module
     * @return bool
     */
    function import($overwritesettings) {
        global $DB, $CFG;

        $params = $this->get_preset_settings();
        $settings = $params->settings;
        $newfields = $params->importfields;
        $currentfields = $params->currentfields;
        $preservedfields = array();

        /* Maps fields and makes new ones */
        if (!empty($newfields)) {
            /* We require an injective mapping, and need to know what to protect */
            foreach ($newfields as $nid => $newfield) {
                $cid = optional_param("field_$nid", -1, PARAM_INT);
                if ($cid == -1) {
                    continue;
                }
                if (array_key_exists($cid, $preservedfields)){
                    print_error('notinjectivemap', 'workbook');
                }
                else $preservedfields[$cid] = true;
            }

            foreach ($newfields as $nid => $newfield) {
                $cid = optional_param("field_$nid", -1, PARAM_INT);

                /* A mapping. Just need to change field params. Workbook kept. */
                if ($cid != -1 and isset($currentfields[$cid])) {
                    $fieldobject = workbook_get_field_from_id($currentfields[$cid]->id, $this->module);
                    foreach ($newfield as $param => $value) {
                        if ($param != "id") {
                            $fieldobject->field->$param = $value;
                        }
                    }
                    unset($fieldobject->field->similarfield);
                    $fieldobject->update_field();
                    unset($fieldobject);
                } else {
                    /* Make a new field */
                    include_once("field/$newfield->type/field.class.php");

                    if (!isset($newfield->description)) {
                        $newfield->description = '';
                    }
                    $classname = 'workbook_field_'.$newfield->type;
                    $fieldclass = new $classname($newfield, $this->module);
                    $fieldclass->insert_field();
                    unset($fieldclass);
                }
            }
        }

        /* Get rid of all old unused workbook */
        if (!empty($preservedfields)) {
            foreach ($currentfields as $cid => $currentfield) {
                if (!array_key_exists($cid, $preservedfields)) {
                    /* Workbook not used anymore so wipe! */
                    print "Deleting field $currentfield->name<br />";

                    $id = $currentfield->id;
                    //Why delete existing workbook records and related comments/ratings??
                    $DB->delete_records('workbook_content', array('fieldid'=>$id));
                    $DB->delete_records('workbook_fields', array('id'=>$id));
                }
            }
        }

        // handle special settings here
        if (!empty($settings->defaultsort)) {
            if (is_numeric($settings->defaultsort)) {
                // old broken value
                $settings->defaultsort = 0;
            } else {
                $settings->defaultsort = (int)$DB->get_field('workbook_fields', 'id', array('workbookid'=>$this->module->id, 'name'=>$settings->defaultsort));
            }
        } else {
            $settings->defaultsort = 0;
        }

        // do we want to overwrite all current workbook settings?
        if ($overwritesettings) {
            // all supported settings
            $overwrite = array_keys((array)$settings);
        } else {
            // only templates and sorting
            $overwrite = array('singletemplate', 'listtemplate', 'listtemplateheader', 'listtemplatefooter',
                               'addtemplate', 'rsstemplate', 'rsstitletemplate', 'csstemplate', 'jstemplate',
                               'asearchtemplate', 'defaultsortdir', 'defaultsort');
        }

        // now overwrite current workbook settings
        foreach ($this->module as $prop=>$unused) {
            if (in_array($prop, $overwrite)) {
                $this->module->$prop = $settings->$prop;
            }
        }

        workbook_update_instance($this->module);

        return $this->cleanup();
    }

    /**
     * Any clean up routines should go here
     * @return bool
     */
    public function cleanup() {
        return true;
    }
}

/**
 * Workbook preset importer for uploaded presets
 */
class workbook_preset_upload_importer extends workbook_preset_importer {
    public function __construct($course, $cm, $module, $filepath) {
        global $USER;
        if (is_file($filepath)) {
            $fp = get_file_packer();
            if ($fp->extract_to_pathname($filepath, $filepath.'_extracted')) {
                fulldelete($filepath);
            }
            $filepath .= '_extracted';
        }
        parent::__construct($course, $cm, $module, $filepath);
    }
    public function cleanup() {
        return fulldelete($this->directory);
    }
}

/**
 * Workbook preset importer for existing presets
 */
class workbook_preset_existing_importer extends workbook_preset_importer {
    protected $userid;
    public function __construct($course, $cm, $module, $fullname) {
        global $USER;
        list($userid, $shortname) = explode('/', $fullname, 2);
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        if ($userid && ($userid != $USER->id) && !has_capability('mod/workbook:manageuserpresets', $context) && !has_capability('mod/workbook:viewalluserpresets', $context)) {
           throw new coding_exception('Invalid preset provided');
        }

        $this->userid = $userid;
        $filepath = workbook_preset_path($course, $userid, $shortname);
        parent::__construct($course, $cm, $module, $filepath);
    }
    public function get_userid() {
        return $this->userid;
    }
}

/**
 * @global object
 * @global object
 * @param object $course
 * @param int $userid
 * @param string $shortname
 * @return string
 */
function workbook_preset_path($course, $userid, $shortname) {
    global $USER, $CFG;

    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    $userid = (int)$userid;

    $path = null;
    if ($userid > 0 && ($userid == $USER->id || has_capability('mod/workbook:viewalluserpresets', $context))) {
        $path = $CFG->workbookroot.'/workbook/preset/'.$userid.'/'.$shortname;
    } else if ($userid == 0) {
        $path = $CFG->dirroot.'/mod/workbook/preset/'.$shortname;
    } else if ($userid < 0) {
        $path = $CFG->workbookroot.'/temp/workbook/'.-$userid.'/'.$shortname;
    }

    return $path;
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the workbook.
 *
 * @param $mform form passed by reference
 */
function workbook_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'workbookheader', get_string('modulenameplural', 'workbook'));
    $mform->addElement('checkbox', 'reset_data', get_string('deleteallentries','workbook'));

    $mform->addElement('checkbox', 'reset_data_notenrolled', get_string('deletenotenrolled', 'workbook'));
    $mform->disabledIf('reset_data_notenrolled', 'reset_data', 'checked');

    $mform->addElement('checkbox', 'reset_data_ratings', get_string('deleteallratings'));
    $mform->disabledIf('reset_data_ratings', 'reset_data', 'checked');

    $mform->addElement('checkbox', 'reset_data_comments', get_string('deleteallcomments'));
    $mform->disabledIf('reset_data_comments', 'reset_data', 'checked');
}

/**
 * Course reset form defaults.
 * @return array
 */
function workbook_reset_course_form_defaults($course) {
    return array('reset_data'=>0, 'reset_data_ratings'=>1, 'reset_data_comments'=>1, 'reset_data_notenrolled'=>0);
}

/**
 * Removes all grades from gradebook
 *
 * @global object
 * @global object
 * @param int $courseid
 * @param string $type optional type
 */
function workbook_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $sql = "SELECT d.*, cm.idnumber as cmidnumber, d.course as courseid
              FROM {workbook} d, {course_modules} cm, {modules} m
             WHERE m.name='workbook' AND m.id=cm.module AND cm.instance=d.id AND d.course=?";

    if ($workbooks = $DB->get_records_sql($sql, array($courseid))) {
        foreach ($workbooks as $workbook) {
            workbook_grade_item_update($workbook, 'reset');
        }
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * workbook responses for course $workbook->courseid.
 *
 * @global object
 * @global object
 * @param object $workbook the workbook submitted from the reset course.
 * @return array status array
 */
function workbook_reset_userworkbook($workbook) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/filelib.php');
    require_once($CFG->dirroot.'/rating/lib.php');

    $componentstr = get_string('modulenameplural', 'workbook');
    $status = array();

    $allrecordssql = "SELECT r.id
                        FROM {workbook_records} r
                             INNER JOIN {workbook} d ON r.workbookid = d.id
                       WHERE d.course = ?";

    $allworkbookssql = "SELECT d.id
                      FROM {workbook} d
                     WHERE d.course=?";

    $rm = new rating_manager();
    $ratingdeloptions = new stdClass;
    $ratingdeloptions->component = 'mod_workbook';
    $ratingdeloptions->ratingarea = 'entry';

    // delete entries if requested
    if (!empty($workbook->reset_data)) {
        $DB->delete_records_select('comments', "itemid IN ($allrecordssql) AND commentarea='workbook_entry'", array($workbook->courseid));
        $DB->delete_records_select('workbook_content', "recordid IN ($allrecordssql)", array($workbook->courseid));
        $DB->delete_records_select('workbook_records', "workbookid IN ($allworkbookssql)", array($workbook->courseid));

        if ($workbooks = $DB->get_records_sql($allworkbookssql, array($workbook->courseid))) {
            foreach ($workbooks as $workbookid=>$unused) {
                fulldelete("$CFG->workbookroot/$workbook->courseid/modworkbook/workbook/$workbookid");

                if (!$cm = get_coursemodule_from_instance('workbook', $workbookid)) {
                    continue;
                }
                $workbookcontext = get_context_instance(CONTEXT_MODULE, $cm->id);

                $ratingdeloptions->contextid = $workbookcontext->id;
                $rm->delete_ratings($ratingdeloptions);
            }
        }

        if (empty($workbook->reset_gradebook_grades)) {
            // remove all grades from gradebook
            workbook_reset_gradebook($workbook->courseid);
        }
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallentries', 'workbook'), 'error'=>false);
    }

    // remove entries by users not enrolled into course
    if (!empty($workbook->reset_data_notenrolled)) {
        $recordssql = "SELECT r.id, r.userid, r.workbookid, u.id AS userexists, u.deleted AS userdeleted
                         FROM {workbook_records} r
                              JOIN {workbook} d ON r.workbookid = d.id
                              LEFT JOIN {user} u ON r.userid = u.id
                        WHERE d.course = ? AND r.userid > 0";

        $course_context = get_context_instance(CONTEXT_COURSE, $workbook->courseid);
        $notenrolled = array();
        $fields = array();
        $rs = $DB->get_recordset_sql($recordssql, array($workbook->courseid));
        foreach ($rs as $record) {
            if (array_key_exists($record->userid, $notenrolled) or !$record->userexists or $record->userdeleted
              or !is_enrolled($course_context, $record->userid)) {
                //delete ratings
                if (!$cm = get_coursemodule_from_instance('workbook', $record->workbookid)) {
                    continue;
                }
                $workbookcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
                $ratingdeloptions->contextid = $workbookcontext->id;
                $ratingdeloptions->itemid = $record->id;
                $rm->delete_ratings($ratingdeloptions);

                $DB->delete_records('comments', array('itemid'=>$record->id, 'commentarea'=>'workbook_entry'));
                $DB->delete_records('workbook_content', array('recordid'=>$record->id));
                $DB->delete_records('workbook_records', array('id'=>$record->id));
                // HACK: this is ugly - the recordid should be before the fieldid!
                if (!array_key_exists($record->workbookid, $fields)) {
                    if ($fs = $DB->get_records('workbook_fields', array('workbookid'=>$record->workbookid))) {
                        $fields[$record->workbookid] = array_keys($fs);
                    } else {
                        $fields[$record->workbookid] = array();
                    }
                }
                foreach($fields[$record->workbookid] as $fieldid) {
                    fulldelete("$CFG->workbookroot/$workbook->courseid/modworkbook/workbook/$record->workbookid/$fieldid/$record->id");
                }
                $notenrolled[$record->userid] = true;
            }
        }
        $rs->close();
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deletenotenrolled', 'workbook'), 'error'=>false);
    }

    // remove all ratings
    if (!empty($workbook->reset_data_ratings)) {
        if ($workbooks = $DB->get_records_sql($allworkbookssql, array($workbook->courseid))) {
            foreach ($workbooks as $workbookid=>$unused) {
                if (!$cm = get_coursemodule_from_instance('workbook', $workbookid)) {
                    continue;
                }
                $workbookcontext = get_context_instance(CONTEXT_MODULE, $cm->id);

                $ratingdeloptions->contextid = $workbookcontext->id;
                $rm->delete_ratings($ratingdeloptions);
            }
        }

        if (empty($workbook->reset_gradebook_grades)) {
            // remove all grades from gradebook
            workbook_reset_gradebook($workbook->courseid);
        }

        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallratings'), 'error'=>false);
    }

    // remove all comments
    if (!empty($workbook->reset_data_comments)) {
        $DB->delete_records_select('comments', "itemid IN ($allrecordssql) AND commentarea='workbook_entry'", array($workbook->courseid));
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallcomments'), 'error'=>false);
    }

    // updating dates - shift may be negative too
    if ($workbook->timeshift) {
        shift_course_mod_dates('workbook', array('timeavailablefrom', 'timeavailableto', 'timeviewfrom', 'timeviewto'), $workbook->timeshift, $workbook->courseid);
        $status[] = array('component'=>$componentstr, 'item'=>get_string('datechanged'), 'error'=>false);
    }

    return $status;
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function workbook_get_extra_capabilities() {
    return array('moodle/site:accessallgroups', 'moodle/site:viewfullnames', 'moodle/rating:view', 'moodle/rating:viewany', 'moodle/rating:viewall', 'moodle/rating:rate', 'moodle/comment:view', 'moodle/comment:post', 'moodle/comment:delete');
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function workbook_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_RATE:                    return true;
        case FEATURE_BACKUP_MOODLE2:          return true;

        default: return null;
    }
}
/**
 * @global object
 * @param array $export
 * @param string $delimiter_name
 * @param object $workbook
 * @param int $count
 * @param bool $return
 * @return string|void
 */
function workbook_export_csv($export, $delimiter_name, $workbookname, $count, $return=false) {
    global $CFG;
    require_once($CFG->libdir . '/csvlib.class.php');
    $delimiter = csv_import_reader::get_delimiter($delimiter_name);
    $filename = clean_filename("{$workbookname}-{$count}_record");
    if ($count > 1) {
        $filename .= 's';
    }
    $filename .= clean_filename('-' . gmdate("Ymd_Hi"));
    $filename .= clean_filename("-{$delimiter_name}_separated");
    $filename .= '.csv';
    if (empty($return)) {
        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=$filename");
        header('Expires: 0');
        header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
        header('Pragma: public');
    }
    $encdelim = '&#' . ord($delimiter) . ';';
    $returnstr = '';
    foreach($export as $row) {
        foreach($row as $key => $column) {
            $row[$key] = str_replace($delimiter, $encdelim, $column);
        }
        $returnstr .= implode($delimiter, $row) . "\n";
    }
    if (empty($return)) {
        echo $returnstr;
        return;
    }
    return $returnstr;
}

/**
 * @global object
 * @param array $export
 * @param string $workbookname
 * @param int $count
 * @return string
 */
function workbook_export_xls($export, $workbookname, $count) {
    global $CFG;
    require_once("$CFG->libdir/excellib.class.php");
    $filename = clean_filename("{$workbookname}-{$count}_record");
    if ($count > 1) {
        $filename .= 's';
    }
    $filename .= clean_filename('-' . gmdate("Ymd_Hi"));
    $filename .= '.xls';

    $filearg = '-';
    $workbook = new MoodleExcelWorkbook($filearg);
    $workbook->send($filename);
    $worksheet = array();
    $worksheet[0] =& $workbook->add_worksheet('');
    $rowno = 0;
    foreach ($export as $row) {
        $colno = 0;
        foreach($row as $col) {
            $worksheet[0]->write($rowno, $colno, $col);
            $colno++;
        }
        $rowno++;
    }
    $workbook->close();
    return $filename;
}

/**
 * @global object
 * @param array $export
 * @param string $workbookname
 * @param int $count
 * @param string
 */
function workbook_export_ods($export, $workbookname, $count) {
    global $CFG;
    require_once("$CFG->libdir/odslib.class.php");
    $filename = clean_filename("{$workbookname}-{$count}_record");
    if ($count > 1) {
        $filename .= 's';
    }
    $filename .= clean_filename('-' . gmdate("Ymd_Hi"));
    $filename .= '.ods';
    $filearg = '-';
    $workbook = new MoodleODSWorkbook($filearg);
    $workbook->send($filename);
    $worksheet = array();
    $worksheet[0] =& $workbook->add_worksheet('');
    $rowno = 0;
    foreach ($export as $row) {
        $colno = 0;
        foreach($row as $col) {
            $worksheet[0]->write($rowno, $colno, $col);
            $colno++;
        }
        $rowno++;
    }
    $workbook->close();
    return $filename;
}

/**
 * @global object
 * @param int $workbookid
 * @param array $fields
 * @param array $selectedfields
 * @return array
 */
function workbook_get_exportworkbook($workbookid, $fields, $selectedfields) {
    global $DB;

    $exportworkbook = array();

    // populate the header in first row of export
    foreach($fields as $key => $field) {
        if (!in_array($field->field->id, $selectedfields)) {
            // ignore values we aren't exporting
            unset($fields[$key]);
        } else {
            $exportworkbook[0][] = $field->field->name;
        }
    }

    $workbookrecords = $DB->get_records('workbook_records', array('workbookid'=>$workbookid));
    ksort($workbookrecords);
    $line = 1;
    foreach($workbookrecords as $record) {
        // get content indexed by fieldid
        if( $content = $DB->get_records('workbook_content', array('recordid'=>$record->id), 'fieldid', 'fieldid, content, content1, content2, content3, content4') ) {
            foreach($fields as $field) {
                $contents = '';
                if(isset($content[$field->field->id])) {
                    $contents = $field->export_text_value($content[$field->field->id]);
                }
                $exportworkbook[$line][] = $contents;
            }
        }
        $line++;
    }
    $line--;
    return $exportworkbook;
}

/**
 * Lists all browsable file areas
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @return array
 */
function workbook_get_file_areas($course, $cm, $context) {
    $areas = array();
    return $areas;
}

/**
 * Serves the workbook attachments. Implements needed access control ;-)
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function workbook_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if ($filearea === 'content') {
        $contentid = (int)array_shift($args);

        if (!$content = $DB->get_record('workbook_content', array('id'=>$contentid))) {
            return false;
        }

        if (!$field = $DB->get_record('workbook_fields', array('id'=>$content->fieldid))) {
            return false;
        }

        if (!$record = $DB->get_record('workbook_records', array('id'=>$content->recordid))) {
            return false;
        }

        if (!$workbook = $DB->get_record('workbook', array('id'=>$field->workbookid))) {
            return false;
        }

        if ($workbook->id != $cm->instance) {
            // hacker attempt - context does not match the contentid
            return false;
        }

        //check if approved
        if ($workbook->approval and !$record->approved and !workbook_isowner($record) and !has_capability('mod/workbook:approve', $context)) {
            return false;
        }

        // group access
        if ($record->groupid) {
            $groupmode = groups_get_activity_groupmode($cm, $course);
            if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
                if (!groups_is_member($record->groupid)) {
                    return false;
                }
            }
        }

        $fieldobj = workbook_get_field($field, $workbook, $cm);

        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_workbook/content/$content->id/$relativepath";

        if (!$fieldobj->file_ok($relativepath)) {
            return false;
        }

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }

        // finally send the file
        send_stored_file($file, 0, 0, true); // download MUST be forced - security!
    }

    return false;
}


function workbook_extend_navigation($navigation, $course, $module, $cm) {
    global $CFG, $OUTPUT, $USER, $DB;

    $rid = optional_param('rid', 0, PARAM_INT);

    $workbook = $DB->get_record('workbook', array('id'=>$cm->instance));
    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);

     $numentries = workbook_numentries($workbook);
    /// Check the number of entries required against the number of entries already made (doesn't apply to teachers)
    if ($workbook->requiredentries > 0 && $numentries < $workbook->requiredentries && !has_capability('mod/workbook:manageentries', get_context_instance(CONTEXT_MODULE, $cm->id))) {
        $workbook->entriesleft = $workbook->requiredentries - $numentries;
        $entriesnode = $navigation->add(get_string('entrieslefttoadd', 'workbook', $workbook));
        $entriesnode->add_class('note');
    }

    $navigation->add(get_string('list', 'workbook'), new moodle_url('/mod/workbook/view.php', array('d'=>$cm->instance)));
    if (!empty($rid)) {
        $navigation->add(get_string('single', 'workbook'), new moodle_url('/mod/workbook/view.php', array('d'=>$cm->instance, 'rid'=>$rid)));
    } else {
        $navigation->add(get_string('single', 'workbook'), new moodle_url('/mod/workbook/view.php', array('d'=>$cm->instance, 'mode'=>'single')));
    }
    $navigation->add(get_string('search', 'workbook'), new moodle_url('/mod/workbook/view.php', array('d'=>$cm->instance, 'mode'=>'asearch')));
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $workbooknode The node to add module settings to
 */
function workbook_extend_settings_navigation(settings_navigation $settings, navigation_node $workbooknode) {
    global $PAGE, $DB, $CFG, $USER;

    $workbook = $DB->get_record('workbook', array("id" => $PAGE->cm->instance));

    $currentgroup = groups_get_activity_group($PAGE->cm);
    $groupmode = groups_get_activity_groupmode($PAGE->cm);

    if (workbook_user_can_add_entry($workbook, $currentgroup, $groupmode, $PAGE->cm->context)) { // took out participation list here!
        if (empty($editentry)) { //TODO: undefined
            $addstring = get_string('add', 'workbook');
        } else {
            $addstring = get_string('editentry', 'workbook');
        }
        $workbooknode->add($addstring, new moodle_url('/mod/workbook/edit.php', array('d'=>$PAGE->cm->instance)));
    }

    if (has_capability(WORKBOOK_CAP_EXPORT, $PAGE->cm->context)) {
        // The capability required to Export workbook records is centrally defined in 'lib.php'
        // and should be weaker than those required to edit Templates, Fields and Presets.
        $workbooknode->add(get_string('exportentries', 'workbook'), new moodle_url('/mod/workbook/export.php', array('d'=>$workbook->id)));
    }
    if (has_capability('mod/workbook:manageentries', $PAGE->cm->context)) {
        $workbooknode->add(get_string('importentries', 'workbook'), new moodle_url('/mod/workbook/import.php', array('d'=>$workbook->id)));
    }

    if (has_capability('mod/workbook:managetemplates', $PAGE->cm->context)) {
        $currenttab = '';
        if ($currenttab == 'list') {
            $defaultemplate = 'listtemplate';
        } else if ($currenttab == 'add') {
            $defaultemplate = 'addtemplate';
        } else if ($currenttab == 'asearch') {
            $defaultemplate = 'asearchtemplate';
        } else {
            $defaultemplate = 'singletemplate';
        }

        $templates = $workbooknode->add(get_string('templates', 'workbook'));

        $templatelist = array ('listtemplate', 'singletemplate', 'asearchtemplate', 'addtemplate', 'rsstemplate', 'csstemplate', 'jstemplate');
        foreach ($templatelist as $template) {
            $templates->add(get_string($template, 'workbook'), new moodle_url('/mod/workbook/templates.php', array('d'=>$workbook->id,'mode'=>$template)));
        }

        $workbooknode->add(get_string('fields', 'workbook'), new moodle_url('/mod/workbook/field.php', array('d'=>$workbook->id)));
        $workbooknode->add(get_string('presets', 'workbook'), new moodle_url('/mod/workbook/preset.php', array('d'=>$workbook->id)));
    }

    if (!empty($CFG->enablerssfeeds) && !empty($CFG->workbook_enablerssfeeds) && $workbook->rssarticles > 0) {
        require_once("$CFG->libdir/rsslib.php");

        $string = get_string('rsstype','forum');

        $url = new moodle_url(rss_get_url($PAGE->cm->context->id, $USER->id, 'mod_workbook', $workbook->id));
        $workbooknode->add($string, $url, settings_navigation::TYPE_SETTING, null, null, new pix_icon('i/rss', ''));
    }
}

/**
 * Save the workbook configuration as a preset.
 *
 * @param stdClass $course The course the workbook module belongs to.
 * @param stdClass $cm The course module record
 * @param stdClass $workbook The workbook record
 * @param string $path
 * @return bool
 */
function workbook_presets_save($course, $cm, $workbook, $path) {
    $fs = get_file_storage();
    $filerecord = new stdClass;
    $filerecord->contextid = WORKBOOK_PRESET_CONTEXT;
    $filerecord->component = WORKBOOK_PRESET_COMPONENT;
    $filerecord->filearea = WORKBOOK_PRESET_FILEAREA;
    $filerecord->itemid = 0;
    $filerecord->filepath = '/'.$path.'/';

    $filerecord->filename = 'preset.xml';
    $fs->create_file_from_string($filerecord, workbook_presets_generate_xml($course, $cm, $workbook));

    $filerecord->filename = 'singletemplate.html';
    $fs->create_file_from_string($filerecord, $workbook->singletemplate);

    $filerecord->filename = 'listtemplateheader.html';
    $fs->create_file_from_string($filerecord, $workbook->listtemplateheader);

    $filerecord->filename = 'listtemplate.html';
    $fs->create_file_from_string($filerecord, $workbook->listtemplate);

    $filerecord->filename = 'listtemplatefooter.html';
    $fs->create_file_from_string($filerecord, $workbook->listtemplatefooter);

    $filerecord->filename = 'addtemplate.html';
    $fs->create_file_from_string($filerecord, $workbook->addtemplate);

    $filerecord->filename = 'rsstemplate.html';
    $fs->create_file_from_string($filerecord, $workbook->rsstemplate);

    $filerecord->filename = 'rsstitletemplate.html';
    $fs->create_file_from_string($filerecord, $workbook->rsstitletemplate);

    $filerecord->filename = 'csstemplate.css';
    $fs->create_file_from_string($filerecord, $workbook->csstemplate);

    $filerecord->filename = 'jstemplate.js';
    $fs->create_file_from_string($filerecord, $workbook->jstemplate);

    $filerecord->filename = 'asearchtemplate.html';
    $fs->create_file_from_string($filerecord, $workbook->asearchtemplate);

    return true;
}

/**
 * Generates the XML for the workbook module provided
 *
 * @global moodle_workbook $DB
 * @param stdClass $course The course the workbook module belongs to.
 * @param stdClass $cm The course module record
 * @param stdClass $workbook The workbook record
 * @return string The XML for the preset
 */
function workbook_presets_generate_xml($course, $cm, $workbook) {
    global $DB;

    // Assemble "preset.xml":
    $presetxmlworkbook = "<preset>\n\n";

    // Raw settings are not preprocessed during saving of presets
    $raw_settings = array(
        'intro',
        'comments',
        'requiredentries',
        'requiredentriestoview',
        'maxentries',
        'rssarticles',
        'approval',
        'defaultsortdir'
    );

    $presetxmlworkbook .= "<settings>\n";
    // First, settings that do not require any conversion
    foreach ($raw_settings as $setting) {
        $presetxmlworkbook .= "<$setting>" . htmlspecialchars($workbook->$setting) . "</$setting>\n";
    }

    // Now specific settings
    if ($workbook->defaultsort > 0 && $sortfield = workbook_get_field_from_id($workbook->defaultsort, $workbook)) {
        $presetxmlworkbook .= '<defaultsort>' . htmlspecialchars($sortfield->field->name) . "</defaultsort>\n";
    } else {
        $presetxmlworkbook .= "<defaultsort>0</defaultsort>\n";
    }
    $presetxmlworkbook .= "</settings>\n\n";
    // Now for the fields. Grab all that are non-empty
    $fields = $DB->get_records('workbook_fields', array('workbookid'=>$workbook->id));
    ksort($fields);
    if (!empty($fields)) {
        foreach ($fields as $field) {
            $presetxmlworkbook .= "<field>\n";
            foreach ($field as $key => $value) {
                if ($value != '' && $key != 'id' && $key != 'workbookid') {
                    $presetxmlworkbook .= "<$key>" . htmlspecialchars($value) . "</$key>\n";
                }
            }
            $presetxmlworkbook .= "</field>\n\n";
        }
    }
    $presetxmlworkbook .= '</preset>';
    return $presetxmlworkbook;
}

function workbook_presets_export($course, $cm, $workbook, $tostorage=false) {
    global $CFG, $DB;

    $presetname = clean_filename($workbook->name) . '-preset-' . gmdate("Ymd_Hi");
    $exportsubdir = "temp/mod_workbook/presetexport/$presetname";
    make_upload_directory($exportsubdir);
    $exportdir = "$CFG->workbookroot/$exportsubdir";

    // Assemble "preset.xml":
    $presetxmlworkbook = workbook_presets_generate_xml($course, $cm, $workbook);

    // After opening a file in write mode, close it asap
    $presetxmlfile = fopen($exportdir . '/preset.xml', 'w');
    fwrite($presetxmlfile, $presetxmlworkbook);
    fclose($presetxmlfile);

    // Now write the template files
    $singletemplate = fopen($exportdir . '/singletemplate.html', 'w');
    fwrite($singletemplate, $workbook->singletemplate);
    fclose($singletemplate);

    $listtemplateheader = fopen($exportdir . '/listtemplateheader.html', 'w');
    fwrite($listtemplateheader, $workbook->listtemplateheader);
    fclose($listtemplateheader);

    $listtemplate = fopen($exportdir . '/listtemplate.html', 'w');
    fwrite($listtemplate, $workbook->listtemplate);
    fclose($listtemplate);

    $listtemplatefooter = fopen($exportdir . '/listtemplatefooter.html', 'w');
    fwrite($listtemplatefooter, $workbook->listtemplatefooter);
    fclose($listtemplatefooter);

    $addtemplate = fopen($exportdir . '/addtemplate.html', 'w');
    fwrite($addtemplate, $workbook->addtemplate);
    fclose($addtemplate);

    $rsstemplate = fopen($exportdir . '/rsstemplate.html', 'w');
    fwrite($rsstemplate, $workbook->rsstemplate);
    fclose($rsstemplate);

    $rsstitletemplate = fopen($exportdir . '/rsstitletemplate.html', 'w');
    fwrite($rsstitletemplate, $workbook->rsstitletemplate);
    fclose($rsstitletemplate);

    $csstemplate = fopen($exportdir . '/csstemplate.css', 'w');
    fwrite($csstemplate, $workbook->csstemplate);
    fclose($csstemplate);

    $jstemplate = fopen($exportdir . '/jstemplate.js', 'w');
    fwrite($jstemplate, $workbook->jstemplate);
    fclose($jstemplate);

    $asearchtemplate = fopen($exportdir . '/asearchtemplate.html', 'w');
    fwrite($asearchtemplate, $workbook->asearchtemplate);
    fclose($asearchtemplate);

    // Check if all files have been generated
    if (! workbook_is_directory_a_preset($exportdir)) {
        print_error('generateerror', 'workbook');
    }

    $filenames = array(
        'preset.xml',
        'singletemplate.html',
        'listtemplateheader.html',
        'listtemplate.html',
        'listtemplatefooter.html',
        'addtemplate.html',
        'rsstemplate.html',
        'rsstitletemplate.html',
        'csstemplate.css',
        'jstemplate.js',
        'asearchtemplate.html'
    );

    $filelist = array();
    foreach ($filenames as $filename) {
        $filelist[$filename] = $exportdir . '/' . $filename;
    }

    $exportfile = $exportdir.'.zip';
    file_exists($exportfile) && unlink($exportfile);

    $fp = get_file_packer('application/zip');
    $fp->archive_to_pathname($filelist, $exportfile);

    foreach ($filelist as $file) {
        unlink($file);
    }
    rmdir($exportdir);

    // Return the full path to the exported preset file:
    return $exportfile;
}

/**
 * Running addtional permission check on plugin, for example, plugins
 * may have switch to turn on/off comments option, this callback will
 * affect UI display, not like pluginname_comment_validate only throw
 * exceptions.
 * Capability check has been done in comment->check_permissions(), we
 * don't need to do it again here.
 *
 * @param stdClass $comment_param {
 *              context  => context the context object
 *              courseid => int course id
 *              cm       => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int itemid
 * }
 * @return array
 */
function workbook_comment_permissions($comment_param) {
    global $CFG, $DB;
    if (!$record = $DB->get_record('workbook_records', array('id'=>$comment_param->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    if (!$workbook = $DB->get_record('workbook', array('id'=>$record->workbookid))) {
        throw new comment_exception('invalidid', 'workbook');
    }
    if ($workbook->comments) {
        return array('post'=>true, 'view'=>true);
    } else {
        return array('post'=>false, 'view'=>false);
    }
}

/**
 * Validate comment parameter before perform other comments actions
 *
 * @param stdClass $comment_param {
 *              context  => context the context object
 *              courseid => int course id
 *              cm       => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int itemid
 * }
 * @return boolean
 */
function workbook_comment_validate($comment_param) {
    global $DB;
    // validate comment area
    if ($comment_param->commentarea != 'workbook_entry') {
        throw new comment_exception('invalidcommentarea');
    }
    // validate itemid
    if (!$record = $DB->get_record('workbook_records', array('id'=>$comment_param->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    if (!$workbook = $DB->get_record('workbook', array('id'=>$record->workbookid))) {
        throw new comment_exception('invalidid', 'workbook');
    }
    if (!$course = $DB->get_record('course', array('id'=>$workbook->course))) {
        throw new comment_exception('coursemisconf');
    }
    if (!$cm = get_coursemodule_from_instance('workbook', $workbook->id, $course->id)) {
        throw new comment_exception('invalidcoursemodule');
    }
    if (!$workbook->comments) {
        throw new comment_exception('commentsoff', 'workbook');
    }
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    //check if approved
    if ($workbook->approval and !$record->approved and !workbook_isowner($record) and !has_capability('mod/workbook:approve', $context)) {
        throw new comment_exception('notapproved', 'workbook');
    }

    // group access
    if ($record->groupid) {
        $groupmode = groups_get_activity_groupmode($cm, $course);
        if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
            if (!groups_is_member($record->groupid)) {
                throw new comment_exception('notmemberofgroup');
            }
        }
    }
    // validate context id
    if ($context->id != $comment_param->context->id) {
        throw new comment_exception('invalidcontext');
    }
    // validation for comment deletion
    if (!empty($comment_param->commentid)) {
        if ($comment = $DB->get_record('comments', array('id'=>$comment_param->commentid))) {
            if ($comment->commentarea != 'workbook_entry') {
                throw new comment_exception('invalidcommentarea');
            }
            if ($comment->contextid != $comment_param->context->id) {
                throw new comment_exception('invalidcontext');
            }
            if ($comment->itemid != $comment_param->itemid) {
                throw new comment_exception('invalidcommentitemid');
            }
        } else {
            throw new comment_exception('invalidcommentid');
        }
    }
    return true;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function workbook_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-workbook-*'=>get_string('page-mod-workbook-x', 'workbook'));
    return $module_pagetype;
}
