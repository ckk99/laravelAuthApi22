<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;


class TestController extends Controller
{
    // Validate the input for payment and collect requests
    protected function validateRequest(Request $request, $rules)
    {
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        return null;
    }


    public function getCreditReport(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'phone' => 'required|numeric|digits:10',
            'pan' => 'string',
            'email' => 'required|email',
            'dob' => 'required|date',
            'aadhaar' => 'string',
            'toMail' => 'boolean',
        ]);

        if ($validation) return $validation;
        
        $authToken = 'Bearer aWSVQNyt+z3IiJHV+YX9Up/wI4iEDlt8Uhq++R8sf5j5JVeo5dQEt1el9ga+ISFB2uEoPAImN9IFX+gK8iBzgWW8B4dJY2jW6YbltYf3syNmfTByc6IxzGILoqayg/IAE1uQdUN7+ufoShwwP5dang==';
        $url = 'https://api.bulkpe.in/client/getCreditReport';
        $payload = [
            'firstName' => $request->firstName,
            'lastName' => $request->lastName,
            'reference' => uniqid('ref_'),
            'phone' => $request->phone,
            'email' => $request->email, 
            'dob' => $request->dob,
            'pan' => $request->pan, //optional
            'aadhaar' => $request->aadhaar, //optional
            'toMail' => $request->toMail, //optional
        ]; 
        // Send the POST request using Laravel's HTTP client
        $response = Http::withHeaders([
            'Authorization' => $authToken
            ])
        ->post($url, $payload);

        // Return the response as it is or handle it as needed
        return response()->json($response->json());
    }

    public function getExperianReport(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'phone' => 'required|numeric|digits:10',
            'pan' => 'required|string',
            'email' => 'email',
        ]);

        if ($validation) return $validation;

        $authToken = 'Bearer aWSVQNyt+z3IiJHV+YX9Up/wI4iEDlt8Uhq++R8sf5j5JVeo5dQEt1el9ga+ISFB2uEoPAImN9IFX+gK8iBzgWW8B4dJY2jW6YbltYf3syNmfTByc6IxzGILoqayg/IAE1uQdUN7+ufoShwwP5dang==';
        $url = 'https://api.bulkpe.in/client/getExperianReport';
        $payload = [
            'firstName' => $request->firstName,
            'lastName' => $request->lastName,
            'reference' => uniqid('ref_'),
            'phone' => $request->phone,
            'email' => $request->email, //optional
            'pan' => $request->pan,
            
        ]; 
        // Send the POST request using Laravel's HTTP client
        $response = Http::withHeaders([
            'Authorization' => $authToken
            ])
        ->post($url, $payload);

        // Return the response as it is or handle it as needed
        return response()->json($response->json());
    }

    public function getCountries(Request $request)
    {
        $url = 'https://uat.apiclub.in/api/v1/countries/all';
        $response = Http::withHeaders([
            'Referer' => 'docs.apiclub.in',
        ])->get($url);
        return response()->json($response->json());
    }

    public function getStates(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'country_code' => 'required|string',
        ]);
        if ($validation) return $validation;
        $countryCode = $request->country_code;
        $url = 'https://uat.apiclub.in/api/v1/states/'.$countryCode; //{$countryCode} or all
        $response = Http::withHeaders([
            'Referer' => 'docs.apiclub.in',
        ])->get($url);
        return response()->json($response->json());
    }

    public function getCities(Request $request)
    {
        $validation = $this->validateRequest($request, [
            'state_code' => 'required|string',
        ]);
        if ($validation) return $validation;
        $stateCode = $request->state_code;
        $url = 'https://uat.apiclub.in/api/v1/cities/'.$stateCode; //{$stateCode} or all
        $response = Http::withHeaders([
            'Referer' => 'docs.apiclub.in',
        ])->get($url);
        return response()->json($response->json());
    } 
    
    public function validateBank()
    {
        try {
            // Make the API request
            $response = Http::withHeaders([
                'Referer' => 'docs.apiclub.in',
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])
            ->post('https://uat.apiclub.in/api/v1/validate/bank');

            // Check if the request was successful
            if ($response->successful()) {
                // Return the successful response data
                return response()->json($response->json());
            } else {
                // Handle the error case
                return response()->json([
                    'error' => 'API request failed',
                    'message' => $response->body(),
                ], $response->status());
            }

        } catch (\Exception $e) {
            // Handle any exceptions (like network errors)
            return response()->json([
                'error' => 'Request failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
