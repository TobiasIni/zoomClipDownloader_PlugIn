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
 * Block zoom_downloader is defined here.
 *
 * @package     block_zoom_downloader
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_zoom_downloader extends block_base {

    /**
     * Initializes class member variables.
     */
    public function init() {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_zoom_downloader');
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }
    
        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }
    
        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
    
        // Aquí puedes realizar la lógica para consultar la API de Zoom
        $token = ''; // Aquí deberías obtener el token de la API
        $meetings = $this->fetch_meetings($token);
    
        if (!empty($meetings)) {
            foreach ($meetings as $meeting) {
                $this->content->items[] = html_writer::tag('p', $meeting['topic']);
            }
        } else {
            $this->content->text = 'No meetings found.';
        }
    
        return $this->content;
    }
    

    /**
     * Defines configuration data.
     *
     * The function is called immediately after init().
     */
    public function specialization() {

        // Load user defined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_zoom_downloader');
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Allow multiple instances in a single course?
     *
     * @return bool True if multiple instances are allowed, false otherwise.
     */
    public function instance_allow_multiple() {
        return true;
    }

    // HAY QUE DESARROLLAR LA LLAMADA A LA API Y VER COMO TOMA EL TOKEN, ESTO ES SOLO UN EJEMPLO
    private function fetch_meetings($token) {
        $url = 'https://api.zoom.us/v2/users/me/meetings';
        $curl = curl_init($url);
    
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
    
        $response = curl_exec($curl);
        curl_close($curl);
    
        return json_decode($response, true);
    }
    

    /**
     * Enables global configuration of the block in settings.php.
     *
     * @return bool True if the global configuration is enabled.
     */
    public function has_config() {
        return true;
    }

    /**
     * Sets the applicable formats for the block.
     *
     * @return string[] Array of pages and permissions.
     */
    public function applicable_formats() {
        return array(
        );
    }
}
