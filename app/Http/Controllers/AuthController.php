<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Resources\UserResource;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Otp;
use App\Models\UserDetail;
use App\Mail\OtpMail;
use Twilio\Rest\Client;
use App\Http\Controllers\ResellerPaymentController;
use App\Services\CommonService;
use Illuminate\Support\Facades\DB;


class AuthController extends Controller
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }
    // Register a new user
    public function register(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            // 'role' => 'required|in:user,admin' // Admin or User role

            'bname' => 'required|string',
            'lname' => 'required|string',
            'phone' => 'required|string',
            'mcc' => 'required|string',
            'type' => 'required|string|in:PROPRIETARY,PARTNERSHIP,PRIVATE,LLP,PUBLIC',
            'city' => 'required|string',
            'district' => 'required|string',
            'stateCode' => 'required|string',
            'pincode' => 'required|string',
            'bpan' => 'required|string',
            'gst' => 'required|string',
            'account' => 'required|string',
            'ifsc' => 'required|string',
            'address1' => 'required|string',
            'address2' => 'required|string',
            'cin' => 'required|string',
            //'msme' => 'required|string',
            'dob' => 'required|date',
            'doi' => 'required|date',
            'url' => 'required|url'
        ]);

        // If validation fails, return validation errors
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Start the transaction
        DB::beginTransaction();

        try {
            // Instantiate the ResellerPaymentController and get the auth token
            $authToken = $this->commonService->getResellerAuthToken();
            if (!$authToken) {
                return response()->json(['message' => 'Authentication failed'], 401);
            }

            // Create the user record
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'ip_address' => $request->ip_address,
                'callback_url' => $request->callback_url,
                'role' => $request->role ?? 'user', // Default role is user
            ]);

            // Prepare merchant data
            $url = 'https://server.paygic.in/api/v2/reseller/createMerchant';
            $data = [
                'rid' => env('PAYGIC_RID'), // Reseller ID
                'bname' => $request->bname, // Business name
                'lname' => $request->lname, // Legal name
                'phone' => $request->phone,
                'email' => $request->email,
                'mcc' => $request->mcc,
                'type' => $request->type,
                'city' => $request->city,
                'district' => $request->district,
                'stateCode' => $request->stateCode,
                'pincode' => $request->pincode,
                'bpan' => $request->bpan,
                'gst' => $request->gst,
                'account' => $request->account,
                'ifsc' => $request->ifsc,
                'address1' => $request->address1,
                'address2' => $request->address2,
                'cin' => $request->cin,
                //'msme' => $request->msme,
                'dob' => $request->dob,
                'doi' => $request->doi,
                'url' => $request->url,
            ];

            // Make the API request to create the merchant
            $response = Http::withHeaders(['token' => $authToken])->post($url, $data);

            if ($response->successful()) {
                // If successful, get the response data
                $res = $response->json();

                // Store merchant details in the user_details table
                UserDetail::create([
                    'user_id' => $user->id,
                    'bname' => $request->bname,
                    'lname' => $request->lname,
                    'phone' => $request->phone,
                    'mcc' => $request->mcc,
                    'type' => $request->type,
                    'city' => $request->city,
                    'district' => $request->district,
                    'stateCode' => $request->stateCode,
                    'pincode' => $request->pincode,
                    'bpan' => $request->bpan,
                    'gst' => $request->gst,
                    'account' => $request->account,
                    'ifsc' => $request->ifsc,
                    'address1' => $request->address1,
                    'address2' => $request->address2,
                    'cin' => $request->cin,
                    // 'msme' => $request->msme,
                    'dob' => $request->dob,
                    'doi' => $request->doi,
                    'url' => $request->url,
                ]);

                // Update the user's merchant ID
                $user->update(['mid' => $res['data']['mid'] ?? null]);

                // Commit the transaction
                DB::commit();

                // Return success response with user and token
                return response()->json([
                    'status' => true,
                    'msg' => 'Merchant created successfully',
                    'data' => $res,
                    'user' => new UserResource($user),
                    'token' => $user->createToken('API Token')->plainTextToken
                ]);
            } else {
                // If merchant creation fails, roll back transaction and return error
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'msg' => 'Failed to create merchant. ' . $response->json()['msg']
                ], 400);
            }
        } catch (\Exception $e) {
            // Log the exception for further debugging
            Log::error("Merchant creation failed: " . $e->getMessage());

            // Rollback the transaction in case of an error
            DB::rollBack();

            // Return error response
            return response()->json([
                'status' => false,
                'msg' => 'An error occurred while creating the merchant. Please try again later.'
            ], 500);
        }
    }
    // Login an existing user
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            
            return response()->json([
                'user' => new UserResource($user), 
                'token' => $user->createToken('API Token')->plainTextToken
            ]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    public function profile(Request $request)
    {
        
        return response()->json(Auth::user());
    }   
    
    // Logout and revoke the token
    public function logout(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user->tokens->each(function ($token) {
                $token->delete();
            });

            return response()->json(['message' => 'Successfully logged out']);
        } else {
            return response()->json(['message' => 'User not authenticated'], 401);
        }
    }

    // Reset password
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $otpRecord = PasswordOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$otpRecord) {
            return response()->json(['error' => 'Invalid OTP.'], 400);
        }

        if (Carbon::now()->greaterThan($otpRecord->expires_at)) {
            return response()->json(['error' => 'OTP has expired.'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        $otpRecord->delete();

        return response()->json(['message' => 'Password reset successfully.'], 200);
    }

    // Send OTP for login
    public function sendLoginOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'phone' => 'nullable|regex:/^\+?[1-9]\d{1,14}$/', // Validate phone number
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $otp = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(5);
        $otpRecord = Otp::updateOrCreate(
            [
                'email' => $request->email,   // Matching condition, can be email or phone
                'phone' => $request->phone,   // Matching condition, can be phone or email
            ],
            [
                'otp' => $otp,                // The OTP value
                'expires_at' => $expiresAt,   // The expiration time for the OTP
            ]
        );
        

        // Send OTP via email or SMS
        if ($request->email) {
            Mail::to($request->email)->send(new OtpMail($otp));
        }

        if ($request->phone) {
            $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
            $twilio->messages->create(
                $request->phone,
                [
                    'from' => env('TWILIO_PHONE_NUMBER'),
                    'body' => "Your OTP is: $otp"
                ]
            );
        }

        return response()->json([
            'otp' => $otp,
            'message' => 'OTP sent successfully!'
        ]);
    }

    // Method to verify OTP and log in the user
    public function verifyOtp(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'phone' => 'nullable|regex:/^\+?[1-9]\d{1,14}$/',
            'otp' => 'required|string|size:6',  // OTP length
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        
        if (!$request->email && !$request->phone) {
            return response()->json(['message' => 'Email or phone is required.'], 400);
        }

        $otpRecord = Otp::where('otp', $request->otp)
                        ->where(function ($query) use ($request) {
                            if ($request->email) {
                                $query->where('email', $request->email);
                            }
                            if ($request->phone) {
                                $query->where('phone', $request->phone);
                            }
                        })
                        ->where('expires_at', '>', Carbon::now()) // Ensure OTP is not expired
                        ->first();

        if (!$otpRecord) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 400);
        }

        $user = User::where(function ($query) use ($request) {
                    if ($request->email) {
                        $query->where('email', $request->email);
                    }
                    if ($request->phone) {
                        $query->where('phone', $request->phone);
                    }
                })->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $otpRecord->delete();

        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'message' => 'OTP verified successfully!',
            'user' => new UserResource($user),
            'token' => $token  // Return the generated token
        ]);
    }

}

