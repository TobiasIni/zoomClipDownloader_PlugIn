<?php
namespace mod_zoomdownloader\task;

defined('MOODLE_INTERNAL') || die();

class zoom_downloader_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('taskname', 'mod_zoomdownloader');
    }

    public function execute() {
        global $DB, $CFG;

        // Configuración de rutas
        $credentials_path = $CFG->dataroot.'/zoomdownloader_credentials.json';
        $config_path = $CFG->dataroot . '/zoomdownloader_configurations.json';

        // Verificación de archivo de configuración
        if (file_exists($config_path)) {
            $config_data = json_decode(file_get_contents($config_path));
        } else {
            mtrace("Error: No se encontró el archivo de configuración en $config_path");
            return;
        }

        $root_folder_id = $config_data->folder_id;
        $debug = $config_data->debug;

        // Función de depuración
        $trace = function($message) use ($debug) {
            if ($debug) {
                mtrace($message);
            }
        };

        // Funciones de impresión
        $imprimir_inicio = function () use ($trace) {
            $trace("----------------------------------------------------------------------------------------------------------");
            $trace("Iniciando la descarga de grabaciones de Zoom...");
        };

        $imprimir_fin = function () use ($trace) {
            $trace("Descarga de grabaciones finalizada...");
            $trace("----------------------------------------------------------------------------------------------------------");
        };

        $imprimir_inicio();

        // Leer credenciales de archivo
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

        // Configuración de autenticación
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

                // Obtener grabaciones de Zoom
                $recordings = $this->getAllZoomRecordings($zoom_links, $zoomToken);

                if ($recordings) {
                    foreach ($recordings as $recording) {
                        $trace("Procesando grabación: " . $recording['id']);
                        
                        // Aquí puedes agregar la lógica para descargar el archivo de Zoom y subirlo a Google Drive.
                    }
                } else {
                    $trace("No se encontraron grabaciones para el curso " . $course->fullname);
                }

            } catch (\Exception $e) {
                mtrace("Error procesando el curso: " . $e->getMessage());
            }
        }

        $imprimir_fin();
    }

    // Función para obtener todas las grabaciones
    private function getAllZoomRecordings($userId, $accessToken) {
        $url = "https://api.zoom.us/v2/users/$userId/recordings";

        // Inicializar cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ));

        // Ejecutar cURL
        $response = curl_exec($ch);

        // Manejar errores de cURL
        if (curl_errno($ch)) {
            echo 'Error de cURL: ' . curl_error($ch);
            return null;
        }

        // Cerrar cURL
        curl_close($ch);

        // Decodificar la respuesta JSON
        $recordingsData = json_decode($response, true);

        return $recordingsData['meetings'] ?? [];
    }
}

?>