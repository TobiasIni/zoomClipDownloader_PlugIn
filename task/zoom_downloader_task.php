<?php
namespace mod_zoomdownloader\task;

defined('MOODLE_INTERNAL') || die();

class export_meetings_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('exportmeetings', 'mod_zoomdownloader');
    }

    public function execute() {

    }
}