<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\CommonService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;

class MerchantController extends Controller
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
}
