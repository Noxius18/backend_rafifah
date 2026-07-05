<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class FcmHelper
{
    public static function sendToTopic($topic, $title, $body, $data = [])
    {
        // Ganti dengan URL Project Firebase kamu bray
        $url = 'https://fcm.googleapis.com/v1/projects/pmb-ramq/messages:send';
        
        // Ambil Google Access Token
        $accessToken = self::getGoogleAccessToken(); 

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

    private static function getGoogleAccessToken()
    {
        $credentialsPath = storage_path('app/private/credentials.json');
        if (!file_exists($credentialsPath)) return '';

        $credentials = json_decode(file_get_contents($credentialsPath), true);
        
        $client = new \Google\Client();
        $client->setAuthConfig($credentials);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->refreshTokenWithAssertion();
        $token = $client->getAccessToken();

        return $token['access_token'] ?? '';
    }
}