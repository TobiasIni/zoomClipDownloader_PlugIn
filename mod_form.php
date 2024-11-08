<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The main mod_zoomdownloader configuration form.
 *
 * @package     mod_zoomdownloader
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package     mod_zoomdownloader
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_zoomdownloader_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $USER , $DB;

        $mform = $this->_form;

                // Asegúrate de que tenemos el ID del curso
                if (isset($this->current->course)) {
                    $course_id = $this->current->course;
                    $course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);
                } else {
                    throw new coding_exception('Course ID not set in the form.');
                }
                        // Obtener la categoría del curso y sus categorías padres
        $course_categories = $DB->get_records_sql("
        WITH RECURSIVE category_hierarchy AS (
            SELECT id, name, parent
            FROM {course_categories}
            WHERE id = :categoryid
            UNION ALL
            SELECT c.id, c.name, c.parent
            FROM {course_categories} c
            INNER JOIN category_hierarchy ch ON c.id = ch.parent
        )
        SELECT id, name
        FROM category_hierarchy
        WHERE id IS NOT NULL
    ", ['categoryid' => $course->category]);

    $mform->addElement('html', '
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tagify/4.9.7/tagify.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/tagify/4.9.7/tagify.min.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var input = document.querySelector("input[name=\'prefijos_grupos\']");
                new Tagify(input, {
                    delimiters: ",", // Delimitadores para separar las etiquetas
                    maxTags: 10, // Número máximo de etiquetas
                    dropdown: {
                        enabled: 0 // No mostrar dropdown
                    }
                });
            });
        </script>
    ');


        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('zoomdownloadername', 'mod_zoomdownloader'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'zoomdownloadername', 'mod_zoomdownloader');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Adding the rest of mod_zoomdownloader settings, spreading all them into this fieldset
        // ... or adding more fieldsets ('header' elements) if needed for better logic.
        $mform->addElement('static', 'label1', 'zoomdownloadersettings', get_string('zoomdownloadersettings', 'mod_zoomdownloader'));
        $mform->addElement('header', 'zoomdownloaderfieldset', get_string('zoomdownloaderfieldset', 'mod_zoomdownloader'));

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
        $context = context_course::instance($this->current->course);
        $hasrole = user_has_role_assignment($USER->id, $DB->get_field('role', 'id', array('shortname' => 'zoomdownloader_drive_role')), $context->id);
        if ($hasrole) {
            // Adding file manager for credentials.
            $options = array(
                'accepted_types' => array('.json'),
                'maxfiles' => 1, // Limitar a un solo archivo
                'subdirs' => 0
            );
            $mform->addElement('filemanager', 'credentials', get_string('credentials', 'mod_zoomdownloader'), null, $options);
            $mform->addHelpButton('credentials', 'credentials_help', 'mod_zoomdownloader');
    
            // Adding text field for folder_id.
            $mform->addElement('text', 'folder_id', get_string('folder_id', 'mod_zoomdownloader'));
            $mform->setType('folder_id', PARAM_TEXT);
            $mform->addHelpButton('folder_id', 'folder_id_help', 'mod_zoomdownloader');
    
            // Adding checkbox field for debug.
            $mform->addElement('advcheckbox', 'debug', get_string('debug', 'mod_zoomdownloader'), get_string('debug_desc', 'mod_zoomdownloader'));
            $mform->setDefault('debug', 0);
            $mform->addHelpButton('debug', 'debug_help', 'mod_zoomdownloader');
        }
    
        $mform->addElement('header', 'others', get_string('others', 'mod_zoomdownloader'));
    
    // Add standard grading elements.
    $this->standard_grading_coursemodule_elements();

    // Add standard elements.
    $this->standard_coursemodule_elements();

    // Add standard buttons.
    $this->add_action_buttons();
    }
    
}
