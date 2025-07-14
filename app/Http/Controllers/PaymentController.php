<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\AfribapayAccessToken;

class PaymentController extends Controller
{
    /**
     * Display a index view.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('pay.index');
    }

    /**
     * Make payment
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function makePayment(Request $request)
    {
        $request->validate([
            'country' => 'required|in:CI,BF,SN',
            'operator' => 'required|in:moov,mtn,orange,wave',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8',
            'amount' => 'required|numeric|min:100',
        ]);

        // Get Access Token
        $token = $this->get_accesstoken();
        
        dd($token);
    }

    /**
     * Get the Access Token
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response $token
     */
    public function get_accesstoken()
    {
        $AccessTokenData = AfribapayAccessToken::where('active', true)->first();
        if ($AccessTokenData) {
            $AccessTokenEnd = Carbon::parse($AccessTokenData->expires_at);
            // Access Token has expired
            if ($AccessTokenEnd->isPast()) {
                // Generate New Access Token
                $tokenData = $this->generate_accesstoken();
                // Make actual Access Token Inactive
                $AccessTokenData->active = false;
                $AccessTokenData->save();
                
                // Create New token and save
                $dateWith20Hours = Carbon::now()->addHours(20);
                $newAccessToken = new AfribapayAccessToken;
                $newAccessToken->token =  $tokenData->data->access_token;
                $newAccessToken->response =  json_encode($tokenData);
                $newAccessToken->expires_at =  $dateWith20Hours;
                $newAccessToken->save();
                // Return Access Token
                return $tokenData->data->access_token;
            }

            // Access Token has not expired
            $token = $AccessTokenData->token;
            return $token;
        }

        // Generate New Access Token
        $tokenData = $this->generate_accesstoken();
        // Create New token and save
        $dateWith20Hours = Carbon::now()->addHours(20);
        $newAccessToken = new AfribapayAccessToken;
        $newAccessToken->token =  $tokenData->data->access_token;
        $newAccessToken->response =  json_encode($tokenData);
        $newAccessToken->expires_at =  $dateWith20Hours;
        $newAccessToken->save();
        // Return Access Token
        return $tokenData->data->access_token;
    }
    
    /**
     * Generate the Access Token from API
     *
     * @return \Illuminate\Response $response
     */
    public function generate_accesstoken()
    {
        $url = env("AFRIBAPAY_API_URL")."/v1/token";

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic '.base64_encode(env('AFRIBAPAY_API_USER').':'.env('AFRIBAPAY_API_KEY')) //  Api_user and Api_key from your merchand account
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }
}