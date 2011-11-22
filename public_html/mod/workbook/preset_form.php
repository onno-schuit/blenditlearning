<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden!');
}
require_once($CFG->libdir . '/formslib.php');


class workbook_existing_preset_form extends moodleform {
    public function definition() {
        $this->_form->addElement('header', 'presets', get_string('usestandard', 'workbook'));
        $this->_form->addHelpButton('presets', 'usestandard', 'workbook');

        $this->_form->addElement('hidden', 'd');
        $this->_form->addElement('hidden', 'action', 'confirmdelete');
        $delete = get_string('delete');
        foreach ($this->_customdata['presets'] as $preset) {
            $this->_form->addElement('radio', 'fullname', null, ' '.$preset->description, $preset->userid.'/'.$preset->shortname);
        }
        $this->_form->addElement('submit', 'importexisting', get_string('choose'));
    }
}

class workbook_import_preset_zip_form extends moodleform {
    public function definition() {
        $this->_form->addElement('header', 'uploadpreset', get_string('fromfile', 'workbook'));
        $this->_form->addHelpButton('uploadpreset', 'fromfile', 'workbook');

        $this->_form->addElement('hidden', 'd');
        $this->_form->addElement('hidden', 'action', 'importzip');
        $this->_form->addElement('filepicker', 'importfile', get_string('chooseorupload', 'workbook'));
        $this->_form->addRule('importfile', null, 'required');
        $this->_form->addElement('submit', 'uploadzip', get_string('import'));
    }
}

class workbook_export_form extends moodleform {
    public function definition() {
        $this->_form->addElement('header', 'exportheading', get_string('exportaszip', 'workbook'));
        $this->_form->addElement('hidden', 'd');
        $this->_form->addElement('hidden', 'action', 'export');
        $this->_form->addElement('submit', 'export', get_string('export', 'workbook'));
    }
}

class workbook_save_preset_form extends moodleform {
    public function definition() {
        $this->_form->addElement('header', 'exportheading', get_string('saveaspreset', 'workbook'));
        $this->_form->addElement('hidden', 'd');
        $this->_form->addElement('hidden', 'action', 'save2');
        $this->_form->addElement('text', 'name', get_string('name'));
        $this->_form->setType('name', PARAM_FILE);
        $this->_form->addRule('name', null, 'required');
        $this->_form->addElement('checkbox', 'overwrite', get_string('overwrite', 'workbook'), get_string('overrwritedesc', 'workbook'));
        $this->_form->addElement('submit', 'saveaspreset', get_string('continue'));
    }
}
