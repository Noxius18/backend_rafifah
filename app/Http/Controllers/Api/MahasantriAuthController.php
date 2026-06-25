<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MahasantriResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MahasantriAuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $mahasantri = User::where('email', $validated['email'])->first();

        if (!$mahasantri || !$mahasantri->password || !Hash::check($validated['password'], $mahasantri->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $plainToken = Str::random(80);
        $mahasantri->apiTokens()->create([
            'name' => $validated['device_name'] ?? 'mobile',
            'token_hash' => hash('sha256', $plainToken),
        ]);

        return response()->json([
            'message' => 'Login berhasil.',
            'token_type' => 'Bearer',
            'access_token' => $plainToken,
            'data' => new MahasantriResource($mahasantri),
        ]);
    }

    public function logout(Request $request)
    {
        $request->attributes->get('mahasantri_api_token')?->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function me(Request $request)
    {
        return new MahasantriResource(
            $request->user()->load(['orangtuas', 'berkas.riwayatUnduhan'])
        );
    }
}
