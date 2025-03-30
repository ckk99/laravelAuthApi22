<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CommonService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Transaction;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    public function merchantFetchIndividual(Request $request)
    {
        $mID = $request->mid;
        $payload = [
            'rid' => env('PAYGIC_RID'),
            'mid' => $mID ?: env('PAYGIC_MID'),
        ];

        $endpoint = 'reseller/merchantFetchIndividual';

        $response = $this->commonService->sendApiRequest($endpoint, $payload);

        return response()->json([
            'message' => 'Merchant fetch individual status',
            'data' => $response,
        ]);
    }

    public function merchantDueDeligence(Request $request)
    {   
        $validation = $this->commonService->validateRequest($request, [
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
        $mID = $request->mid;;
        $payload = [
            'rid' => env('PAYGIC_RID'),
            'mid' => $mID ?: env('PAYGIC_MID'),
            'id' => $request->id,
            'name' => $request->name,
            'pan' => $request->pan,
            'type' => $request->type,
            'poa' => $request->poa,
            'dob' => $request->dob,
            'gender' => $request->gender,
        ];

        $endpoint = 'reseller/merchantDueDeligence';

        $response = $this->commonService->sendApiRequest($endpoint, $payload);

        return response()->json([
            'message' => 'Merchant due deligence status',
            'data' => $response,
        ]);
    }

    public function merchantCompleteOnboarding(Request $request)
    {
        $mID = $request->mid;;
        $payload = [
            'rid' => env('PAYGIC_RID'),
            'mid' => $mID ?: env('PAYGIC_MID'),
        ];

        $endpoint = 'reseller/merchantCompleteOnboarding';

        $response = $this->commonService->sendApiRequest($endpoint, $payload);

        return response()->json([
            'message' => 'Merchant complete onboarding status',
            'data' => $response,
        ]);
    }

    public function transactionDetails(Request $request)
    {
        $transaction = Transaction::get();

        return response()->json([
            'message' => 'Merchant transactions status',
            'data' => $transaction,
        ]);
    }

    public function fetchMerchant(Request $request)
    {
        if ($request->isMethod('post')) { 
            $validation = $this->commonService->validateRequest($request, [
                'userId' => 'required|string',
            ]);
    
            if ($validation) return $validation;
            $users = User::where('role', 'user')
                    ->where('id', $request->userId)
                    ->with('userDetail')->get()->toArray();
                
            return response()->json([
                'message' => 'Fetch merchant details',
                'data' => $users,
            ]);
        }
        $users = User::where('role', 'user')->with('UserDetail')->get()->toArray();
        // dd($users);
        // foreach ($users as $user) {
        //     $userDetail = $user->userDetail; // Access the related UserDetail directly
        // }
        // $users = User::where('role', 'user')->with('userDetail')->get()->map(function ($user) {
        //     return array_merge($user->toArray(), $user->userDetail ? $user->userDetail->toArray() : []);
        // });
       
        return response()->json([
            'message' => 'Fetch all merchants details',
            'data' => $users,
        ]);
    }

    public function deleteMerchant(Request $request)
    {
        $validation = $this->commonService->validateRequest($request, [
            'userId' => 'required|string',
        ]);

        if ($validation) return $validation;
        $users = User::where('id', $request->userId)->delete();
        return response()->json([
            'message' => 'Merchant delete status',
        ]);
    }
}
