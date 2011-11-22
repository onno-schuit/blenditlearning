<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_workbook_mod_form extends moodleform_mod {

    function definition() {
        global $CFG, $DB;

        $mform =& $this->_form;

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor(true, get_string('intro', 'workbook'));

        $mform->addElement('date_selector', 'timeavailablefrom', get_string('availablefromdate', 'workbook'), array('optional'=>true));

        $mform->addElement('date_selector', 'timeavailableto', get_string('availabletodate', 'workbook'), array('optional'=>true));

        $mform->addElement('date_selector', 'timeviewfrom', get_string('viewfromdate', 'workbook'), array('optional'=>true));

        $mform->addElement('date_selector', 'timeviewto', get_string('viewtodate', 'workbook'), array('optional'=>true));


        $countoptions = array(0=>get_string('none'))+
                        (array_combine(range(1, WORKBOOK_MAX_ENTRIES),//keys
                                        range(1, WORKBOOK_MAX_ENTRIES)));//values
        $mform->addElement('select', 'requiredentries', get_string('requiredentries', 'workbook'), $countoptions);
        $mform->addHelpButton('requiredentries', 'requiredentries', 'workbook');

        $mform->addElement('select', 'requiredentriestoview', get_string('requiredentriestoview', 'workbook'), $countoptions);
        $mform->addHelpButton('requiredentriestoview', 'requiredentriestoview', 'workbook');

        $mform->addElement('select', 'maxentries', get_string('maxentries', 'workbook'), $countoptions);
        $mform->addHelpButton('maxentries', 'maxentries', 'workbook');

        $ynoptions = array(0 => get_string('no'), 1 => get_string('yes'));
        $mform->addElement('select', 'comments', get_string('comments', 'workbook'), $ynoptions);

        $mform->addElement('select', 'approval', get_string('requireapproval', 'workbook'), $ynoptions);
        $mform->addHelpButton('approval', 'requireapproval', 'workbook');

        if($CFG->enablerssfeeds && $CFG->workbook_enablerssfeeds){
            $mform->addElement('select', 'rssarticles', get_string('numberrssarticles', 'workbook') , $countoptions);
        }

        $this->standard_grading_coursemodule_elements();

        $this->standard_coursemodule_elements();

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();
    }

    function workbook_preprocessing(&$default_values){
        parent::workbook_preprocessing($default_values);
    }

}

