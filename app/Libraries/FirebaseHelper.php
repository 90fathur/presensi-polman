<?php

namespace App\Libraries;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

class FirebaseHelper
{
    private static $cachedAccessToken = null;

    /**
     * Send FCM Push Notification using HTTP v1 API
     */
    public static function sendPushNotification($deviceToken, $title, $body, $data = [])
    {
        $jsonKeyFilePath = FCPATH . '../firebase-service-account.json';

        if (!file_exists($jsonKeyFilePath)) {
            log_message('error', 'FCM Service Account JSON file missing at: ' . $jsonKeyFilePath);
            return false;
        }

        try {
            // Get the project ID from the JSON file
            $jsonContent = json_decode(file_get_contents($jsonKeyFilePath), true);
            $projectId = $jsonContent['project_id'];

            $accessToken = cache('firebase_oauth_token');

            if (!$accessToken) {
                // Scope required for FCM
                $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

                // Initialize credentials
                $credentials = new ServiceAccountCredentials($scopes, $jsonKeyFilePath);
                $token = $credentials->fetchAuthToken(HttpHandlerFactory::build());

                if (!isset($token['access_token'])) {
                    log_message('error', 'FCM Failed to generate access token.');
                    return false;
                }

                $accessToken = $token['access_token'];
                cache()->save('firebase_oauth_token', $accessToken, 3300); // Cache for 55 mins
            }

            // Build the FCM payload
            $message = [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => empty($data) ? (object)[] : $data
                ]
            ];

            $url = 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send';

            // cURL Request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local env if needed
            curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Max 3 seconds execution
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // Max 2 seconds to connect

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                log_message('info', 'FCM Sent successfully: ' . $response);
                return true;
            } else {
                log_message('error', 'FCM Error ' . $httpCode . ': ' . $response);
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', 'FCM Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send Bulk FCM Push Notifications using curl_multi_init
     * This prevents PHP-FPM from blocking and crashing AAPanel when sending hundreds of notifications.
     */
    public static function sendPushNotificationBulk($deviceTokens, $title, $body, $data = [])
    {
        if (empty($deviceTokens)) return true;

        $jsonKeyFilePath = FCPATH . '../firebase-service-account.json';

        if (!file_exists($jsonKeyFilePath)) {
            log_message('error', 'FCM Service Account JSON file missing at: ' . $jsonKeyFilePath);
            return false;
        }

        try {
            $jsonContent = json_decode(file_get_contents($jsonKeyFilePath), true);
            $projectId = $jsonContent['project_id'];

            $accessToken = cache('firebase_oauth_token');
            if (!$accessToken) {
                $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
                $credentials = new ServiceAccountCredentials($scopes, $jsonKeyFilePath);
                $token = $credentials->fetchAuthToken(HttpHandlerFactory::build());

                if (!isset($token['access_token'])) {
                    return false;
                }
                $accessToken = $token['access_token'];
                cache()->save('firebase_oauth_token', $accessToken, 3300);
            }
            $url = 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send';

            $mh = curl_multi_init();
            $ch_array = [];

            foreach ($deviceTokens as $i => $token) {
                $message = [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => empty($data) ? (object)[] : $data
                    ]
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);

                curl_multi_add_handle($mh, $ch);
                $ch_array[$i] = $ch;
            }

            // Execute all queries simultaneously without spiking CPU to 100%
            $running = null;
            do {
                $status = curl_multi_exec($mh, $running);
                if ($running) {
                    // Wait for network activity. This prevents the CPU from spinning at 100%
                    curl_multi_select($mh);
                }
            } while ($running && $status == CURLM_OK);

            foreach ($ch_array as $ch) {
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }
            curl_multi_close($mh);

            return true;
        } catch (\Exception $e) {
            log_message('error', 'FCM Bulk Exception: ' . $e->getMessage());
            return false;
        }
    }
}
