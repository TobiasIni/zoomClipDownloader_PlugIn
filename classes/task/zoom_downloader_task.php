<?php
namespace mod_zoomdownloader\task;

defined('MOODLE_INTERNAL') || die();

class zoom_downloader_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('taskname', 'mod_zoomdownloader');
    }

    public function execute() {
        global $DB, $CFG;
        
        // Configuraciones
        $credentials_path = $CFG->dataroot.'/zoomdownloader_credentials.json';
        $config_path = $CFG->dataroot . '/zoomdownloader_configurations.json';

        if (file_exists($config_path)) {
            $config_data = json_decode(file_get_contents($config_path));
        } else {
            mtrace("Error: No se encontró el archivo de configuración en $config_path");
            return;
        }

        $root_folder_id = $config_data->folder_id;
        $debug = $config_data->debug;

        $trace = function($message) use ($debug) {
            if ($debug) {
                mtrace($message);
            }
        };

        $imprimir_inicio = function () use ($trace) {
            $trace("----------------------------------------------------------------------------------------------------------");
            $trace("Iniciando la descarga de grabaciones de Zoom...");
        };

        $imprimir_fin = function () use ($trace) {
            $trace("Descarga de grabaciones finalizada...");
            $trace("----------------------------------------------------------------------------------------------------------");
        };

        $imprimir_inicio();

        // Obtener credenciales
        $leer_credenciales = function($credentials_path) use ($trace) {
            $trace("Leyendo credenciales...");
            $credentials = json_decode(file_get_contents($credentials_path), true);
            if (!$credentials) {
                $trace("Error al leer las credenciales desde $credentials_path");
                return false;
            }
            $trace("Credenciales leídas correctamente.");
            return $credentials;
        };

        // Configurar autenticación
        $authManager = new \AuthManager(
            get_config('mod_zoomdownloader', 'zoom'),
            get_config('mod_zoomdownloader', 'google'),
            $DB
        );

        // Obtener tokens
        $zoomToken = $authManager->getZoomAccessToken();
        $googleToken = $authManager->getGoogleAccessToken();

        if (!$zoomToken || !$googleToken) {
            $trace("Error: No se pudieron obtener los tokens de acceso");
            $imprimir_fin();
            return;
        }

        // Obtener instancias activas
        $instances = $DB->get_records('zoomdownloader', ['enabled' => 1]);
        
        foreach ($instances as $instance) {
            try {
                $course = $DB->get_record('course', ['id' => $instance->course]);
                $trace("Procesando curso: " . $course->fullname);

                // Obtener enlaces de Zoom
                $zoom_links = $this->obtener_enlaces_zoom($course->id);
                if (empty($zoom_links)) {
                    $trace("No se encontraron enlaces de Zoom para el curso " . $course->fullname);
                    continue;
                }

                // Descargar grabaciones
                $grabaciones = $this->descargar_grabaciones($zoom_links, $zoomToken);
                if (!$grabaciones) {
                    $trace("Error al descargar grabaciones para el curso " . $course->fullname);
                    continue;
                }

                // Crear estructura de carpetas en Drive
                $folder_ids = $this->crear_estructura_carpetas($googleToken, $root_folder_id, $course);
                if (!$folder_ids) {
                    $trace("Error al crear estructura de carpetas para el curso " . $course->fullname);
                    continue;
                }

                // Subir grabaciones a Drive
                $this->subir_a_drive($googleToken, $grabaciones, $folder_ids['final_folder_id'], $course);
                
                $trace("Procesamiento completado para el curso " . $course->fullname);
            } catch (\Exception $e) {
                $trace("Error en el curso {$course->fullname}: " . $e->getMessage());
            }
        }

        $imprimir_fin();
    }

    private function obtener_enlaces_zoom($course_id) {
        // Implementar lógica para obtener enlaces de Zoom
        return ZoomClipDownloader_get_zoom_meeting_links($course_id);
    }

    private function descargar_grabaciones($zoom_links, $zoomToken) {
        // Implementar lógica para descargar grabaciones
        return ZoomClipDownloader_download_zoom_recordings($zoom_links, $zoomToken);
    }

    private function crear_estructura_carpetas($token, $root_folder_id, $course) {
        // Implementar lógica para crear estructura de carpetas en Drive
        // Similar a la función del exportanotas pero adaptada para Zoom
    }

    private function subir_a_drive($token, $grabaciones, $folder_id, $course) {
        // Implementar lógica para subir archivos a Drive
        return ZoomClipDownloader_upload_to_google_drive($token, $grabaciones, $folder_id);
    }
}