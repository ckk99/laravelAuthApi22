<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CommonService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ResellerPaymentController extends Controller
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    public function generateToken(Request $request)
    {
        // Call the service to get the token
        $authToken = $this->commonService->getResellerAuthToken();
        
        if ($authToken) {
            return response()->json([
                'message' => 'Token generated successfully',
                'token' => $authToken,
            ]);
        } else {
            return response()->json([
                'message' => 'Failed to generate token',
            ], 500);
        }
    }
    
    /**
     * Create a payment request
     */
    public function createPaymentRequest(Request $request)
    {
        $validation = $this->commonService->validateRequest($request, [
            'amount' => 'required|numeric|min:1|max:100000',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_mobile' => 'required|string',
        ]);

        if ($validation) return $validation;
        $mID = Auth::user()->mid;
        $payload = [
            'rid' => env('PAYGIC_RID'),
            'mid' => $mID ?: env('PAYGIC_MID'),
            'merchantReferenceId' => uniqid('ref_'), // Generate unique reference ID
            'amount' => $request->amount,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_mobile' => $request->customer_mobile,
        ];


        $endpoint = 'reseller/createPaymentRequest';

        $response = $this->commonService->sendApiRequest($endpoint, $payload);
        Transaction::create([
            'rid' => env('PAYGIC_RID'),
            'mid' => $payload['mid'],
            'amount' => $request->amount,
            'saralPeID' => uniqid('slp_'),
            'merchantReferenceId' => $payload['merchantReferenceId'],
            'customer_name' => $payload['customer_name'],
            'customer_email' => $payload['customer_email'],
            'customer_mobile' => $payload['customer_mobile'],
            'payment_mode' => 'upi',
            'status' => 'pending',
            'paygicReferenceId' => $response['data']['paygicReferenceId'] ?? null,
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
        try {
            Log::info('Webhook Received:', $request->all());

            $txnStatus = $request->input('txnStatus');
            $message = $request->input('msg', 'No message provided');
            $data = $request->input('data', []);

            if (!is_array($data)) {
                return response()->json(['error' => 'Invalid data format'], 400);
            }

            // Handle Transaction
            if ($request->input('type') === 'TRANSACTION') {
                $transaction = Transaction::updateOrCreate(
                    ['merchantReferenceId' => $data['merchantReferenceId']],
                    [
                        'rid' => $data['rid'],
                        'mid' => $data['mid'],
                        'paygicReferenceId' => $data['paygicReferenceId'],
                        'amount' => $data['amount'],
                        'utr' => $data['UTR'] ?? null,
                        'payerName' => $data['payerName'] ?? null,
                        'payeeUPI' => $data['payeeUPI'] ?? null,
                        'successDate' => isset($data['successDate'])
                            ? \Carbon\Carbon::createFromTimestampMs($data['successDate'])->toDateTimeString()
                            : null,
                        'status' => $txnStatus === 'SUCCESS' ? 'SUCCESS' : 'FAIL',
                    ]
                );
                return response()->json(['message' => 'Transaction stored successfully'], 200);
            }

            // Handle Settlement
            if ($request->input('type') === 'SETTLEMENT') {
                $settlement = Settlement::updateOrCreate(
                    ['paygicReferenceNumber' => $data['paygicReferenceNumber']],
                    [
                        'mid' => $data['mid'],
                        'rid' => $data['rid'],
                        'amount' => $data['amount'],
                        'utr' => $data['utr'] ?? null,
                        'bankReferenceNumber' => $data['bankReferenceNumber'] ?? null,
                        'mode' => $data['mode'] ?? null,
                        'initiationDate' => isset($data['initiationDate'])
                            ? \Carbon\Carbon::parse($data['initiationDate'])->toDateTimeString()
                            : null,
                        'status' => $txnStatus === 'SUCCESS' ? 'SUCCESS' : 'FAIL',
                    ]
                );
                return response()->json(['message' => 'Settlement stored successfully'], 200);
            }

            return response()->json(['error' => 'Invalid transaction type'], 400);
        } catch (\Exception $e) {
            Log::error('Error processing webhook: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while processing the transaction.'], 500);
        }
    }

    /**
     * Create a collect request
     */
    public function createCollectRequest(Request $request)
    {
        $validation = $this->commonService->validateRequest($request, [
            'amount' => 'required|numeric|min:1|max:100000',
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_mobile' => 'required|string',
        ]);

        if ($validation) return $validation;
        $mID = Auth::user()->mid;
        $payload = [
            'rid' => env('PAYGIC_RID'),
            'mid' => $mID ?: env('PAYGIC_MID'),
            'merchantReferenceId' => uniqid('ref_'), // Generate unique reference ID
            'amount' => $request->amount,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_mobile' => $request->customer_mobile,
            'vpa' => 'upiId@ptaxis', // Example UPI ID
            'remark' => 'Collection Payment', // Example remark
        ];

        $endpoint = 'reseller/createCollectRequest';

        $response = $this->commonService->sendApiRequest($endpoint, $payload);
        
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
            'paygicReferenceId' => $response['data']['paygicReferenceId'] ?? null,  
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
        $validation = $this->commonService->validateRequest($request, [
            'merchantReferenceId' => 'required|string',
        ]);

        if ($validation) return $validation;
        $mID = Auth::user()->mid;
        $payload = [
            'rid' => env('PAYGIC_RID'),
            'mid' => $mID ?: env('PAYGIC_MID'),
            'merchantReferenceId' => $request->merchantReferenceId,
        ];

        $endpoint = 'reseller/checkPaymentStatus';

        $response = $this->commonService->sendApiRequest($endpoint, $payload);

        return response()->json([
            'message' => 'Payment status retrieval status',
            'data' => $response,
        ]);
    }

    /**
     * Get transaction details
     */
    public function transactionDetails(Request $request)
    {
        $transaction = Transaction::get();
        return response()->json([
            'message' => 'Transaction details',
            'data' => $transaction,
        ]);
    }

    public function resetApiKey(Request $request)
    {
        $user = Auth::user();
        $api_key = Str::random(60);
        $user->api_key = $api_key;
        $user->save();
        return response()->json(['message' => 'API key reset successfully']);
    }

    public function updateCallbackUrl(Request $request)
    {
        $user = Auth::user();
        $user->callback_url = $request->callback_url;
        $user->save();
        return response()->json(['message' => 'Callback URL updated successfully']);
    }

    public function ipWhiteList(Request $request)
    {
        $user = Auth::user();
        $user->ip_address = $request->ip_address;
        $user->save();
        return response()->json(['message' => 'IP white list updated successfully']);
    }
}
