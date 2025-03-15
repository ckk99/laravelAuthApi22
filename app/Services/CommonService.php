<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\ResellerAuthToken;

class CommonService
{
    // Function to get reseller auth token
    public function getResellerAuthToken()
    {
            // Check if there is a valid token in the database
        $resellerToken = ResellerAuthToken::latest()->first();

        if ($resellerToken && $resellerToken->expires_at > now()) {
            // If the token is found and still valid, return the token
            return $resellerToken->token;
        }

        
        // Fallback to generating a new token if not found or expired
        $baseUrl = 'https://server.paygic.in/api/v2/';
        $endpoint = 'reseller/createResellerAuthToken';
        $url = $baseUrl . $endpoint;
        $data = [
            'rid' => env('PAYGIC_RID'),
            'password' => env('PAYGIC_RID_PASSWORD'),
        ];

        // Send the POST request to generate the token
        $response = Http::withHeaders(['Content-Type' => 'application/json'])->post($url, $data);

        if ($response->successful()) {
            // Extract the token and expiration time from the response
            $authToken = $response->json()['data']['token'];
            $expiresIn = 30; // The token is valid for 30 days (as per your description)
            $expiresAt = now()->addDays($expiresIn);

            // Store the token in the database
            ResellerAuthToken::Create(
                ['token' => $authToken],
                ['expires_at' => $expiresAt]
            );

            return $authToken;
        }

        // If not successful, log the error and return null
        Log::error('Failed to get PayGic Auth Token', ['response' => $response->body()]);
        return null;
    }

    // Function to make API requests
    public function sendApiRequest($endpoint, $payload)
    {
        $authToken = $this->getResellerAuthToken();
        if (!$authToken) {
            return response()->json(['message' => 'Authentication failed'], 401);
        }

        try {
            $baseUrl = 'https://server.paygic.in/api/v2/';
            $url = $baseUrl . $endpoint;
            // Send the POST request to the PayGic API
            $response = Http::withHeaders(['token' => $authToken])->post($url, $payload);

            if ($response->successful()) {
                return $response->json();
            } else {
                return response()->json([
                    'message' => 'Request failed',
                    'error' => $response->json(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('PayGic API request failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred during the API request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Function to validate incoming requests
    public function validateRequest($request, $rules)
    {
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        return null;
    }
}
