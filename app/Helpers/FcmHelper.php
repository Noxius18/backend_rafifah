<?php

namespace App\Helpers;

use RuntimeException;
use Illuminate\Support\Facades\Http;

class FcmHelper
{
    public static function sendToTopic($topic, $title, $body, $data = [], ?callable $accessTokenResolver = null)
    {
        // Ganti dengan URL Project Firebase kamu bray
        $url = 'https://fcm.googleapis.com/v1/projects/pmb-ramq/messages:send';
        
        // Ambil Google Access Token
        $accessToken = self::getGoogleAccessToken($accessTokenResolver);

        // FIX LOGIKA DATA: Jika array kosong [], paksa jadi objek kosong {} agar Firebase v1 gak protes bray
        $dataPayload = empty($data) ? (object)[] : $data;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'message' => [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $dataPayload // <-- Menggunakan payload yang sudah difix
            ]
        ]);

        return $response->json();
    }

    private static function getGoogleAccessToken(?callable $accessTokenResolver = null)
    {
        if ($accessTokenResolver) {
            $accessToken = $accessTokenResolver();
            if (!is_string($accessToken) || $accessToken === '') {
                throw new RuntimeException('Firebase access token tidak bisa dibuat.');
            }

            return $accessToken;
        }

        $credentialsPath = storage_path('app/private/credentials.json');
        if (!file_exists($credentialsPath)) {
            throw new RuntimeException('Firebase credentials tidak ditemukan di storage/app/private/credentials.json.');
        }

        $credentials = json_decode(file_get_contents($credentialsPath), true);
        if (!is_array($credentials) || $credentials === []) {
            throw new RuntimeException('Firebase credentials tidak valid.');
        }
        
        $client = new \Google\Client();
        $client->setAuthConfig($credentials);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->refreshTokenWithAssertion();
        $token = $client->getAccessToken();

        $accessToken = $token['access_token'] ?? '';
        if ($accessToken === '') {
            throw new RuntimeException('Firebase access token tidak bisa dibuat.');
        }

        return $accessToken;
    }
}
