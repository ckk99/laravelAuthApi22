<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class MerchantPaymentController extends Controller
{
    // Helper method to get the authentication token
    protected function getMerchantAuthToken()
    {
        // Check if the auth token is cached or stored
        if ($authToken = env('PAYGIC_MID_AUTH_TOKEN')) {
            return $authToken;
        }

        // API endpoint
        $url = 'https://server.paygic.in/api/v2/createMerchantToken';
        
        // Data for POST request
        $data = [
            'mid' => env('PAYGIC_MID'),       // Merchant ID
            'password' => env('PAYGIC_MID_PASSWORD')  // Merchant password
        ];

        // Make the POST request using Laravel's HTTP Client
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $data);

        if ($response->successful()) {
            $authToken = $response->json()['data']['token']; 
            return $authToken;
        }
        // If not successful, log the error and return a default value
        Log::error('Failed to get PayGic Auth Token', ['response' => $response->body()]);
        return null;
    }

    /**
     * Helper method to send HTTP requests to PayGic API
     *
     * @param string $url
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendPayGicRequest(string $url, array $data)
    {
        try {
            $authToken = $this->getMerchantAuthToken();

            $response = Http::withHeaders([
                'token' => $authToken,
            ])->post($url, $data);

            if ($response->successful()) {
                return response()->json([
                    'message' => 'Request successful',
                    'data' => $response->json(),
                ], 200);
            }

            return response()->json([
                'message' => 'Request failed',
                'error' => $response->json(),
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('PayGic API request failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while processing the request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate input for payment request
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validateRequest(Request $request, $rules)
    {
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        return null;
    }

    public function createPaymentPage(Request $request)
    {
        // Validate the incoming data
        $validation = $this->validateRequest($request, [
            'amount' => 'required|numeric|min:1|max:100000',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_mobile' => 'required|string',
            // 'redirect_URL' => $request->redirect_URL,
            // 'failed_URL' => $request->failed_URL,
        ]);

        if ($validation) return $validation;

        $paymentData = [
            'mid' => env('PAYGIC_MID'),
            'merchantReferenceId' => uniqid('ref_'),  // Generate a unique reference ID
            'amount' => $request->amount,
            'customer_mobile' => $request->customer_mobile,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            // 'redirect_URL' => $request->redirect_URL,
            // 'failed_URL' => $request->failed_URL,
        ];
        $url = 'https://server.paygic.in/api/v2/createPaymentPage';
        // Send payment request to PayGic API
        return $this->sendPayGicRequest($url, $paymentData);
    }

    public function createPaymentRequest(Request $request)
    {
        // Validate the incoming data
        $validation = $this->validateRequest($request, [
            'amount' => 'required|numeric|min:1|max:100000',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_mobile' => 'required|string',
        ]);

        if ($validation) return $validation;

        $paymentData = [
            'mid' => env('PAYGIC_MID'),
            'merchantReferenceId' => uniqid('ref_'),  // Generate a unique reference ID
            'amount' => $request->amount,
            'customer_mobile' => $request->customer_mobile,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
        ];
        $url = 'https://server.paygic.in/api/v2/createPaymentRequest';
        // Send payment request to PayGic API
        return $this->sendPayGicRequest($url, $paymentData);
    }

    public function createCollectRequest(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'amount' => 'required|numeric|min:1|max:100000',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_mobile' => 'required|string',
        ]);

        if ($validation) return $validation;

        // Set the payload for the collection request
        $payload = [
            'mid' => env('PAYGIC_MID'),
            'merchantReferenceId' => uniqid('ref_'), // Generate a unique reference ID
            'amount' => $request->amount,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_mobile' => $request->customer_mobile,
            'vpa' => 'upiId@ptaxis', // Example UPI ID
            'remark' => 'Collection Payment', // Example remark
        ];
        $url = 'https://server.paygic.in/api/v2/createCollectRequest';
        // Send collect request to PayGic API
        return $this->sendPayGicRequest($url, $payload);
    }

    public function checkPaymentStatus(Request $request)
    {
        // Validate the incoming request
        $validation = $this->validateRequest($request, [
            'merchantReferenceId' => 'required|string',
        ]);

        if ($validation) return $validation;

        // Set the payload for the payment status request
        $payload = [
            'mid' => env('PAYGIC_MID'),
            'merchantReferenceId' => $request->merchantReferenceId,
        ];
        $url = 'https://server.paygic.in/api/v2/checkPaymentStatus';
        // Send payment status request to PayGic API
        return $this->sendPayGicRequest($url, $payload);
    }
}
