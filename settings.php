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
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Settings for the Zoom Downloader plugin
 *
 * @package     mod_zoomdownloader
 * @category    admin
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('mod_zoomdownloader_settings', new lang_string('pluginname', 'mod_zoomdownloader'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // Sección de ajustes generales.
        $settings->add(new admin_setting_heading(
            'mod_zoomdownloader/general_settings',
            get_string('generalsettings', 'mod_zoomdownloader'),
            ''
        ));

        // Campo para configurar la clave API de Zoom.
        $settings->add(new admin_setting_configtext(
            'mod_zoomdownloader/zoom_api_key',
            get_string('zoomapikey', 'mod_zoomdownloader'),
            get_string('zoomapikey_desc', 'mod_zoomdownloader'),
            '',
            PARAM_TEXT
        ));

        // Campo para configurar el correo electrónico de soporte.
        $settings->add(new admin_setting_configtext(
            'mod_zoomdownloader/support_email',
            get_string('supportemail', 'mod_zoomdownloader'),
            get_string('supportemail_desc', 'mod_zoomdownloader'),
            '',
            PARAM_EMAIL
        ));
    }

    $ADMIN->add('modsettings', $settings);
}
