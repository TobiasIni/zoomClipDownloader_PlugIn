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
 * Event for viewing a course module.
 *
 * @package     mod_zoomdownloader
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_zoomdownloader\event;

defined('MOODLE_INTERNAL') || die();

class course_module_viewed extends base {
   protected function init(){
    $this->data['objecttable'] = 'zoomdownloader';
    $this->data['crud'] = 'r'; // Read operation
    $this->data['edulevel'] = self::LEVEL_OTHER;
   }

    protected function get_custom_validation_error() {
        return '';
    }
   }

