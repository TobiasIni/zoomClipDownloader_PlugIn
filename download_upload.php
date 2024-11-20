<?php
require_once 'vendor/autoload.php'; // Cargar las dependencias de Google API
require_once 'lib.php'; 

// Configuración del cliente de Google
$client = new Google_Client();
$client->setAuthConfig('ruta/a/tu/credentials.json'); // Cambia esta ruta a la correcta para tu archivo de credenciales
$client->addScope(Google_Service_Drive::DRIVE);

// Crear el servicio de Google Drive
$service = new Google_Service_Drive($client);

// Configura tu ID de la carpeta de destino en Google Drive
$folderId = '12obtKfcyT-SEBUjiy0y2Pn2rzMR-3C4f';

// Autenticación para Zoom
$accessToken = zcd_get_zoom_access_token(); // Reemplaza esto con tu token de acceso de Zoom
$userId = 'itesort+asc-aux@gmail.com';

// Paso 1: Obtener la lista de grabaciones desde Zoom
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.zoom.us/v2/users/$userId/recordings");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Authorization: Bearer $accessToken"
));
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

// Paso 2: Verificar y procesar la URL de descarga de las grabaciones
if (!empty($data['meetings'])) {
    foreach ($data['meetings'] as $meeting) {
        if (!empty($meeting['recording_files'])) {
            foreach ($meeting['recording_files'] as $recording) {
                if ($recording['file_type'] == 'MP4') { // Filtra solo archivos de video MP4
                    $downloadUrl = $recording['download_url']; // URL de descarga del archivo de Zoom
                    $localFilePath = '/ruta/temporal/video_descargado.mp4'; // Ruta temporal para guardar el archivo descargado

                    // Paso 3: Descargar el archivo desde Zoom
                    $ch = curl_init($downloadUrl);
                    $fp = fopen($localFilePath, 'w+');
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        "Authorization: Bearer $accessToken" // Incluye el token para la descarga
                    ));
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);

                    // Verificar que el archivo se descargó correctamente
                    if (file_exists($localFilePath)) {
                        // Paso 4: Subir el archivo a Google Drive
                        $fileMetadata = new Google_Service_Drive_DriveFile(array(
                            'name' => 'ZoomGrabacion.mp4', // Nombre del archivo en Drive
                            'parents' => array($folderId)
                        ));
                        $content = file_get_contents($localFilePath);
                        $file = $service->files->create($fileMetadata, array(
                            'data' => $content,
                            'mimeType' => 'video/mp4',
                            'uploadType' => 'multipart',
                            'fields' => 'id'
                        ));
                        echo "Archivo subido con ID: " . $file->id . "\n";

                        // Opcional: Eliminar el archivo local después de la subida
                        unlink($localFilePath);
                    } else {
                        echo "Error al descargar el archivo de Zoom.\n";
                    }
                }
            }
        }
    }
} else {
    echo "No se encontraron grabaciones.\n";
}
?>