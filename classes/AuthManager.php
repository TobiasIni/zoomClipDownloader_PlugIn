<?php
class AuthManager {
    private $zoomClientId;
    private $zoomClientSecret;
    private $zoomRedirectUri;
    private $googleClientId;
    private $googleClientSecret;
    private $googleRedirectUri;

    private $db; // Asume que tienes una conexión a la base de datos

    public function __construct($zoomConfig, $googleConfig, $dbConnection) {
        $this->zoomClientId = $zoomConfig['client_id'];
        $this->zoomClientSecret = $zoomConfig['client_secret'];
        $this->zoomRedirectUri = $zoomConfig['url_base'] . '?wstoken=' . $zoomConfig['token'] . '&wsfunction=core_course_get_courses&moodlewsrestformat=json';


        $this->googleClientId = $googleConfig['client_id'];
        $this->googleClientSecret = $googleConfig['client_secret'];
        $this->googleRedirectUri = $googleConfig['redirect_uri'];

        $this->db = $dbConnection; // Conexión a la base de datos
    }

    // Método para obtener el token de acceso de Zoom
    public function getZoomAccessToken() {
        // Verifica si el token de acceso está almacenado y aún es válido
        $accessToken = $this->getStoredAccessToken('zoom');
        if ($this->isTokenExpired($accessToken)) {
            // Si el token ha caducado, usa el refresh token para obtener uno nuevo
            $accessToken = $this->refreshZoomToken();
        }
        return $accessToken;
    }

    // Método para refrescar el token de Zoom
    private function refreshZoomToken() {
        $refreshToken = $this->getStoredRefreshToken('zoom');
        $url = "https://zoom.us/oauth/token?grant_type=refresh_token&refresh_token=$refreshToken";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic " . base64_encode("{$this->zoomClientId}:{$this->zoomClientSecret}")
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            $this->storeAccessToken('zoom', $data['access_token'], $data['expires_in']);
            $this->storeRefreshToken('zoom', $data['refresh_token']);
            return $data['access_token'];
        } else {
            throw new Exception("Error al refrescar el token de Zoom");
        }
    }

    // Método para obtener el token de acceso de Google Drive
    public function getGoogleAccessToken() {
        // Verifica si el token de acceso está almacenado y aún es válido
        $accessToken = $this->getStoredAccessToken('google');
        if ($this->isTokenExpired($accessToken)) {
            // Si el token ha caducado, usa el refresh token para obtener uno nuevo
            $accessToken = $this->refreshGoogleToken();
        }
        return $accessToken;
    }

    // Método para refrescar el token de Google
    private function refreshGoogleToken() {
        $refreshToken = $this->getStoredRefreshToken('google');
        $url = "https://oauth2.googleapis.com/token";

        $postFields = http_build_query([
            'client_id' => $this->googleClientId,
            'client_secret' => $this->googleClientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            $this->storeAccessToken('google', $data['access_token'], $data['expires_in']);
            return $data['access_token'];
        } else {
            throw new Exception("Error al refrescar el token de Google");
        }
    }

    // Métodos auxiliares para manejar tokens
    private function getStoredAccessToken($service) {
        $stmt = $this->db->prepare("SELECT access_token FROM tokens WHERE service = :service");
        $stmt->execute(['service' => $service]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['access_token'] : null;
    }

    private function getStoredRefreshToken($service) {
        $stmt = $this->db->prepare("SELECT refresh_token FROM tokens WHERE service = :service");
        $stmt->execute(['service' => $service]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['refresh_token'] : null;
    }

    private function storeAccessToken($service, $accessToken, $expiresIn) {
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
        $stmt = $this->db->prepare("UPDATE tokens SET access_token = :access_token, expires_at = :expires_at WHERE service = :service");
        $stmt->execute([
            'access_token' => $accessToken,
            'expires_at' => $expiresAt,
            'service' => $service
        ]);
    }

    private function storeRefreshToken($service, $refreshToken) {
        $stmt = $this->db->prepare("UPDATE tokens SET refresh_token = :refresh_token WHERE service = :service");
        $stmt->execute([
            'refresh_token' => $refreshToken,
            'service' => $service
        ]);
    }

    private function isTokenExpired($accessToken) {
        $stmt = $this->db->prepare("SELECT expires_at FROM tokens WHERE access_token = :access_token");
        $stmt->execute(['access_token' => $accessToken]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $expiresAt = strtotime($result['expires_at']);
            return time() > $expiresAt;
        }
        return true; // Si no se encuentra el token, se asume que ha caducado
    }
}
