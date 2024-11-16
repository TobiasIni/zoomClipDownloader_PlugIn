<?php
// Archivo: zoom_downloader_cron.php
require_once(__DIR__ . '/../../../config.php'); // Cargar configuración de Moodle.
require_once($CFG->dirroot . '/local/zoom/lib.php'); // Si tienes funciones específicas para Zoom.
require_once($CFG->dirroot . '/path/to/vendor/autoload.php'); // Autoloader de Composer.

// Función para subir a Google Drive.
function upload_to_google_drive($filePath, $mimeType, $folderId = null) {
    $client = new Google_Client();
    $client->setAuthConfig($CFG->dirroot . '/path/to/credentials.json');
    $client->addScope(Google_Service_Drive::DRIVE);

    $service = new Google_Service_Drive($client);

    $file = new Google_Service_Drive_DriveFile();
    $file->setName(basename($filePath));

    if ($folderId) {
        $file->setParents([$folderId]);
    }

    $content = file_get_contents($filePath);
    $uploadedFile = $service->files->create($file, [
        'data' => $content,
        'mimeType' => $mimeType,
        'uploadType' => 'multipart',
    ]);

    return $uploadedFile->id;
}

// Descarga de video desde Zoom.
function download_video_from_zoom($zoomMeetingId, $destinationPath) {
    // Aquí debes usar la API de Zoom para obtener el enlace de descarga del video.
    $zoomApiUrl = "https://api.zoom.us/v2/meetings/$zoomMeetingId/recordings";
    $token = 'YOUR_ZOOM_JWT_TOKEN'; // Reemplazar con tu token.

    $ch = curl_init($zoomApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if (isset($data['download_url'])) {
        file_put_contents($destinationPath, fopen($data['download_url'], 'r'));
        return true;
    }

    return false;
}

// Proceso principal.
function process_zoom_to_drive($zoomMeetingId, $folderId) {
    global $CFG;

    $tempDir = $CFG->tempdir . '/zoom_videos';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    $videoPath = $tempDir . "/$zoomMeetingId.mp4";

    // Paso 1: Descargar video desde Zoom.
    if (download_video_from_zoom($zoomMeetingId, $videoPath)) {
        mtrace("Video descargado: $videoPath");

        // Paso 2: Subir video a Google Drive.
        $mimeType = 'video/mp4';
        try {
            $fileId = upload_to_google_drive($videoPath, $mimeType, $folderId);
            mtrace("Video subido a Google Drive. ID: $fileId");
        } catch (Exception $e) {
            mtrace("Error subiendo a Google Drive: " . $e->getMessage());
        }

        // Paso 3: Limpiar archivo temporal.
        unlink($videoPath);
    } else {
        mtrace("Error descargando el video desde Zoom.");
    }
}

// Invocar el proceso.
$zoomMeetingId = 'YOUR_ZOOM_MEETING_ID'; // Cambia esto con el ID real de la reunión.
$googleDriveFolderId = 'YOUR_GOOGLE_DRIVE_FOLDER_ID'; // ID de la carpeta en Google Drive.
process_zoom_to_drive($zoomMeetingId, $googleDriveFolderId);

mtrace("Proceso finalizado.");
