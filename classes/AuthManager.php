<?php
require 'vendor/autoload.php';

use Google\Client;
use Google\Service\Drive;

class AuthManager {
    private $zoomClientId;
    private $zoomClientSecret;
    private $googleClientId;
    private $googleClientSecret;
    private $googleRedirectUri;
    private $db;

    public function __construct($zoomConfig, $googleConfig, $dbConnection) {
        $this->zoomClientId = $zoomConfig['rtxK9CMvStqkRNpMb5onZg'];
        $this->zoomClientSecret = $zoomConfig['WA3842JmPz6pLK6B9alNBJI429jcV6Ni'];
        
        $this->googleClientId = $googleConfig['777311179093-j0pbpl7vq687c85t71rf09hs59n9505l.apps.googleusercontent.com'];
        $this->googleClientSecret = $googleConfig['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'];
        $this->googleRedirectUri = $googleConfig['http://localhost/?redirect=0'];

        $this->db = $dbConnection;
        session_start();
    }

    // Obtiene el token de acceso de Zoom
    public function getZoomAccessToken() {
        $accessToken = $this->getStoredAccessToken('zoom');
        
        if ($this->isTokenExpired($accessToken)) {
            $accessToken = $this->fetchNewZoomAccessToken();
        }

        return $accessToken;
    }

    // Obtiene un nuevo access token de Zoom usando Server-to-Server OAuth
    private function fetchNewZoomAccessToken() {
        $url = "https://zoom.us/oauth/token?grant_type=client_credentials";

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
            return $data['access_token'];
        } else {
            throw new Exception("Error al obtener el token de acceso de Zoom");
        }
    }

    // Autentica y obtiene el cliente de Google Drive
    public function authenticateGoogle() {
        $client = new Client();
        $client->setAuthConfig('path/to/your_client_secret.json');
        $client->addScope(Drive::DRIVE_FILE);
        $client->setRedirectUri($this->googleRedirectUri);
    
        if (!isset($_SESSION['access_token'])) {
            if (!isset($_GET['code'])) {
                $authUrl = $client->createAuthUrl();
                header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
                exit;
            } else {
                $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
                $_SESSION['access_token'] = $token['access_token'];
                $this->storeAccessToken('google', $token['access_token'], $token['expires_in']);
            }
        } else {
            $client->setAccessToken($_SESSION['access_token']);
        }
    
        return new Drive($client);
    }

    // Métodos auxiliares para manejar tokens
    private function getStoredAccessToken($service) {
        $stmt = $this->db->prepare("SELECT access_token FROM tokens WHERE service = :service");
        $stmt->execute(['service' => $service]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['access_token'] : null;
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
    public function getZoomConfig() {
        return [
            'clientId' => $this->zoomClientId,
            'clientSecret' => $this->zoomClientSecret
        ];
    }
    
    public function getGoogleConfig() {
        return [
            'clientId' => $this->googleClientId,
            'clientSecret' => $this->googleClientSecret,
            'redirectUri' => $this->googleRedirectUri
        ];
    }
    
}
