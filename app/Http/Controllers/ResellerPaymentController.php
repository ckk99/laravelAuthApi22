<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class ResellerPaymentController extends Controller
{
    // Helper method to get the authentication token
    public function getResellerAuthToken()
    {
        if ($authToken = env('PAYGIC_RID_AUTH_TOKEN')) {
            return $authToken;
        }

        // Fallback to generating a new token if not available in env
        $url = 'https://server.paygic.in/api/v2/reseller/createResellerAuthToken';
        $data = [
            'rid' => env('PAYGIC_RID'),
            'password' => env('PAYGIC_RID_PASSWORD'),
        ];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])->post($url, $data);

        if ($response->successful()) {
            $authToken = $response->json()['data']['token']; 
            return $authToken;
        }

        // If not successful, log the error and return a default value
        Log::error('Failed to get PayGic Auth Token', ['response' => $response->body()]);
        return null;
    }

    // Reusable method to make API requests
    protected function sendApiRequest($url, $payload)
    {
        $authToken = $this->getResellerAuthToken();
        if (!$authToken) {
            return response()->json(['message' => 'Authentication failed'], 401);
        }

        try {
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

    // Validate the input for payment and collect requests
    protected function validateRequest(Request $request, $rules)
    {
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        return null;
    }

    /**
     * Create a payment request
     */
    public function createPaymentRequest(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'amount' => 'required|numeric|min:1|max:100000',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_mobile' => 'required|string',
        ]);

        if ($validation) return $validation;
        $mID = Auth::user()->mid;
        $payload = [
            'rid' => env('PAYGIC_RID'),
            'mid' => $mID = null ? env('PAYGIC_MID') : $mID,
            'merchantReferenceId' => uniqid('ref_'), // Generate unique reference ID
            'saralPeID' => uniqid('slp_'), // Generate unique reference ID
            'amount' => $request->amount,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_mobile' => $request->customer_mobile,
        ];

        $url = 'https://server.paygic.in/api/v2/reseller/createPaymentRequest';

        $response = $this->sendApiRequest($url, $payload);
        Transaction::create([
            'rid' => env('PAYGIC_RID'),
            'mid' => $payload['mid'],
            'amount' => $request->amount,
            'saralPeID' => $payload['saralPeID'],
            'merchantReferenceId' => $payload['merchantReferenceId'],
            'customer_name' => $payload['customer_name'],
            'customer_email' => $payload['customer_email'],
            'customer_mobile' => $payload['customer_mobile'],
            'payment_mode' => 'upi',
            'status' => 'pending',
            'paygicReferenceId' => $response['data']['paygicReferenceId'],
        ]);
        return response()->json([
            'message' => 'Payment request creation status',
            'data' => $response,
        ]);
    }

    /**
     * Check payment status
     */

    public function callback(Request $request)
    {
        // Retrieve the transaction data
        $transactionData = $request->input('data');

        // Log the transaction data for debugging
        Log::info('Transaction Callback Received:', $transactionData);

        // Process the transaction data, for example, save it to the database
        // Assuming you have a Transaction model, you can create or update the transaction record
        try {
            // Example: saving the transaction to the database
            Transaction::updateOrCreate(
                ['paygicReferenceId' => $transactionData['paygicReferenceId']],
                [
                    'status' => $transactionData['txnStatus'],
                    'payment_type' => $transactionData['type'],
                    'payment_mode' => $transactionData['payment_mode'],
                    'utr' => $transactionData['utr'],
                    'payer_name' => $transactionData['payer_name'],
                    'payee_upi' => $transactionData['payee_upi'],
                    'success_date' => $transactionData['success_date'],
                ]
            );

            // Return success response
            return response()->json(['message' => 'Transaction successfully processed.'], 200);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Error processing transaction callback: ' . $e->getMessage());

            // Return an error response
            return response()->json(['error' => 'An error occurred while processing the transaction.'], 500);
        }
    }
    /**
     * Create a collect request
     */
    public function createCollectRequest(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'amount' => 'required|numeric|min:1|max:100000',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_mobile' => 'required|string',
        ]);

        if ($validation) return $validation;
        $mID = Auth::user()->mid;
        $payload = [
            'rid' => env('PAYGIC_RID'),
            'mid' => $mID = null ? env('PAYGIC_MID') : $mID,
            'merchantReferenceId' => uniqid('ref_'), // Generate unique reference ID
            'amount' => $request->amount,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_mobile' => $request->customer_mobile,
            'vpa' => 'upiId@ptaxis', // Example UPI ID
            'remark' => 'Collection Payment', // Example remark
        ];

        $url = 'https://server.paygic.in/api/v2/reseller/createCollectRequest';

        $response = $this->sendApiRequest($url, $payload);
        
        Transaction::create([
            'rid' => env('PAYGIC_RID'),
            'mid' => $payload['mid'],
            'amount' => $request->amount,
            'merchantReferenceId' => $payload['merchantReferenceId'],
            'customer_name' => $payload['customer_name'],
            'customer_email' => $payload['customer_email'],
            'customer_mobile' => $payload['customer_mobile'],
            'payment_mode' => 'upi',
            'status' => 'pending',
            'paygicReferenceId' => $response['data']['paygicReferenceId'],
        ]);
        return response()->json([
            'message' => 'Collect request creation status',
            'data' => $response,
        ]);
    }

    /**
     * Check payment status
     */
    public function checkPaymentStatus(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'merchantReferenceId' => 'required|string',
        ]);

        if ($validation) return $validation;
        $mID = Auth::user()->mid;
        $payload = [
            'rid' => env('PAYGIC_RID'),
            'mid' => $mID = null ? env('PAYGIC_MID') : $mID,
            'merchantReferenceId' => $request->merchantReferenceId,
        ];

        $url = 'https://server.paygic.in/api/v2/reseller/checkPaymentStatus';

        $response = $this->sendApiRequest($url, $payload);

        return response()->json([
            'message' => 'Payment status retrieval status',
            'data' => $response,
        ]);
    }

    public function merchantFetchIndividual(Request $request)
    {
        $mID = Auth::user()->mid;
        $payload = [
            'rid' => env('PAYGIC_RID'),
            'mid' => $mID = null ? env('PAYGIC_MID') : $mID,
        ];

        $url = 'https://server.paygic.in/api/v2/reseller/merchantFetchIndividual';

        $response = $this->sendApiRequest($url, $payload);

        return response()->json([
            'message' => 'Merchant fetch individual status',
            'data' => $response,
        ]);
    }

    public function merchantDueDeligence(Request $request)
    {   
        $validation = $this->validateRequest($request, [
            // 'rid' => 'required|string',
            // 'mid' => 'required|string',
            'id' => 'required|string',
            'name' => 'required|string',
            'pan' => 'required|string',
            'type' => 'required|string|in:AADHAAR,DRIVING_LICENSE,VOTER_ID',
            'poa' => 'required|string',
            'dob' => 'required|date',
            'gender' => 'required|string|in:M,F',
        ]);

        if ($validation) return $validation;
        $mID = Auth::user()->mid;
        $payload = [
            'rid' => env('PAYGIC_RID'),
            'mid' => $mID = null ? env('PAYGIC_MID') : $mID,
            'id' => $request->id,
            'name' => $request->name,
            'pan' => $request->pan,
            'type' => $request->type,
            'poa' => $request->poa,
            'dob' => $request->dob,
            'gender' => $request->gender,
        ];

        $url = 'https://server.paygic.in/api/v2/reseller/merchantDueDeligence';

        $response = $this->sendApiRequest($url, $payload);

        return response()->json([
            'message' => 'Merchant due deligence status',
            'data' => $response,
        ]);
    }

    public function merchantCompleteOnboarding(Request $request)
    {
        $mID = Auth::user()->mid;
        $payload = [
            'rid' => env('PAYGIC_RID'),
            'mid' => $mID = null ? env('PAYGIC_MID') : $mID,
        ];

        $url = 'https://server.paygic.in/api/v2/reseller/merchantCompleteOnboarding';

        $response = $this->sendApiRequest($url, $payload);

        return response()->json([
            'message' => 'Merchant complete onboarding status',
            'data' => $response,
        ]);
    }

}
