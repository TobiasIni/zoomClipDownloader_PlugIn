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
 * Library of interface functions and constants.
 *
 * @package     mod_zoomdownloader
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function zoomdownloader_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_zoomdownloader into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_zoomdownloader_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function zoomdownloader_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('zoomdownloader', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_zoomdownloader in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_zoomdownloader_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function zoomdownloader_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('zoomdownloader', $moduleinstance);
}

/**
 * Removes an instance of the mod_zoomdownloader from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function zoomdownloader_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('zoomdownloader', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('zoomdownloader', array('id' => $id));

    return true;
}

/**
 * Registra las tareas y eventos para el plugin.
 * Aquí podrías agregar funcionalidades como la integración con otras APIs.
 *
 * @return void
 */
function ZoomClipDownloader_register_tasks() {
    // Código para registrar tareas, por ejemplo, tareas cron programadas
    // Puedes usar las funciones de Moodle para programar tareas recurrentes
    // Ejemplo: registrar una tarea cron
    // $task = new \core\task\scheduled_task();
    // $task->set_component('local_your_plugin');
    // $task->set_name('Nombre de la tarea');
    // $task->set_schedule('* * * * *'); // Cron expression
    // \core\task\manager::configure_scheduled_task($task);
}

/**
 * Obtiene los enlaces de Zoom del módulo de Zoom en un curso específico.
 *
 * @param int $courseid El ID del curso en Moodle.
 * @return array Un arreglo con los enlaces de Zoom encontrados.
 */
function ZoomClipDownloader_get_zoom_meeting_links($courseid) {
    global $DB;

    // Consulta para obtener todos los módulos de tipo "zoom" del curso
    $sql = "SELECT zm.id, zm.join_url
            FROM {zoom} zm
            JOIN {course_modules} cm ON cm.instance = zm.id
            WHERE cm.course = :courseid";
    
    $params = ['courseid' => $courseid];
    $results = $DB->get_records_sql($sql, $params);

    $zoom_meeting_links = [];
    
    // Recorre los resultados y agrega los enlaces de la reunión de Zoom al arreglo
    foreach ($results as $record) {
        $zoom_meeting_links[] = $record->join_url;
    }

    return $zoom_meeting_links;
}

/**
 * Descarga las grabaciones de Zoom para un conjunto de enlaces de reuniones.
 *
 * @param array $zoom_links Un arreglo de enlaces de reuniones de Zoom.
 * @param string $access_token El token de acceso para la API de Zoom.
 * @return void
 */
function ZoomClipDownloader_download_zoom_recordings($zoom_links, $access_token) {
    global $USER;

    // Inicializa el sistema de archivos de Moodle
    $fs = get_file_storage();

    foreach ($zoom_links as $link) {
        // Extraer el ID de la reunión desde el enlace
        preg_match('/j\/(\d+)/', $link, $matches);
        if (isset($matches[1])) {
            $meeting_id = $matches[1];

            // URL de la API de Zoom para listar las grabaciones de la reunión
            $url = "https://api.zoom.us/v2/meetings/$meeting_id/recordings";

            // Configurar la solicitud cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $access_token"
            ]);

            // Ejecutar la solicitud y obtener la respuesta
            $response = curl_exec($ch);
            if ($response === false) {
                echo "Error en la solicitud de Zoom: " . curl_error($ch) . "<br>";
                continue;
            }
            curl_close($ch);

            // Decodificar la respuesta JSON
            $recordings = json_decode($response, true);

            if (isset($recordings['recording_files'])) {
                foreach ($recordings['recording_files'] as $file) {
                    $download_url = $file['download_url'];
                    $file_type = $file['file_type'];
                    $recording_name = "$meeting_id-" . uniqid() . ".$file_type";

                    // Descargar la grabación
                    $ch_download = curl_init();
                    curl_setopt($ch_download, CURLOPT_URL, $download_url);
                    curl_setopt($ch_download, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch_download, CURLOPT_HTTPHEADER, [
                        "Authorization: Bearer $access_token"
                    ]);
                    $file_data = curl_exec($ch_download);
                    if ($file_data === false) {
                        echo "Error en la descarga de la grabación: " . curl_error($ch_download) . "<br>";
                        curl_close($ch_download);
                        continue;
                    }
                    curl_close($ch_download);

                    // Guardar la grabación en el sistema de archivos de Moodle
                    $file_record = [
                        'contextid' => context_user::instance($USER->id)->id,
                        'component' => 'user',
                        'filearea' => 'private',
                        'itemid' => 0,
                        'filepath' => '/',
                        'filename' => $recording_name,
                    ];

                    // Crear el archivo en Moodle
                    $fs->create_file_from_string($file_record, $file_data);
                    echo "Grabación guardada en Moodle: $recording_name<br>";
                }
            } else {
                echo "No se encontraron grabaciones para la reunión con ID: $meeting_id<br>";
            }
        } else {
            echo "No se pudo extraer el ID de la reunión del enlace: $link<br>";
        }
    }
}

require_once 'vendor/autoload.php'; // Asegúrate de que la ruta sea correcta

/**
 * Sube las grabaciones almacenadas en Moodle a Google Drive.
 *
 * @param string $accessToken El token de acceso para la API de Google Drive.
 * @return void
 */
function ZoomClipDownloader_upload_to_google_drive($accessToken) {
    global $USER, $DB;

    // Inicializa el sistema de archivos de Moodle
    $fs = get_file_storage();
    $context = context_user::instance($USER->id);

    // Obtén todos los archivos en el área privada del usuario
    $files = $fs->get_area_files($context->id, 'user', 'private', false, 'filename', false);

    // Configura el cliente de Google Drive
    $client = new Google_Client();
    $client->setAccessToken($accessToken);

    $driveService = new Google_Service_Drive($client);

    foreach ($files as $file) {
        $filename = $file->get_filename();
        $filepath = $file->get_content_file_handle();

        // Crea un archivo en Google Drive
        $driveFile = new Google_Service_Drive_DriveFile();
        $driveFile->setName($filename);

        // Sube el archivo a Google Drive
        try {
            $driveService->files->create($driveFile, [
                'data' => file_get_contents($filepath),
                'mimeType' => 'application/octet-stream',
                'uploadType' => 'multipart'
            ]);
            echo "Archivo subido a Google Drive: $filename<br>";
        } catch (Exception $e) {
            echo "Error al subir $filename a Google Drive: " . $e->getMessage() . "<br>";
        }
    }
}