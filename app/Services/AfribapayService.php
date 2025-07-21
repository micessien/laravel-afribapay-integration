<?php

namespace App\Services;

use App\Models\AfribapayAccessToken;
use Carbon\Carbon;

class AfribapayService {

    /**
     * Initialize Payment
     */
    public function initializePayment($formData)
    {
        $url = config("services.afribapay.url")."/v1/pay/payin";
        // Get Access Token
        $token = $this->getAccessToken();

        $fields_string = json_encode($formData);
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
            CURLOPT_POSTFIELDS => $fields_string,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$token // Add the Bearer token here
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

     /**
     * Payment verification
     */
    public function paymentVerification($transaction_id)
    {
        $url = config("services.afribapay.url")."/v1/status?transaction_id=".$transaction_id;
        // Get Access Token
        $token = $this->getAccessToken();

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$token,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

     /**
     * Get the Access Token (from database)
     */
    public function getAccessToken()
    {
        $AccessTokenData = AfribapayAccessToken::where('active', true)->first();
        if ($AccessTokenData) {
            $AccessTokenEnd = Carbon::parse($AccessTokenData->expires_at);
            // Access Token has expired
            if ($AccessTokenEnd->isPast()) {
                // Generate New Access Token
                $tokenData = $this->generateAccessToken();
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
        $tokenData = $this->generateAccessToken();
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
     */
    private function generateAccessToken()
    {
        $url = config("services.afribapay.url")."/v1/token";

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
                'Authorization: Basic '.base64_encode(config("services.afribapay.user").':'.config("services.afribapay.key")) //  Api_user and Api_key from your merchand account
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

    /**
     * Signature verification: Create a hash of the payload using the key
     */
    public function afribapay_sign($payload, $key)
    {
        $signature = hash_hmac('sha256', $payload, $key);
        return $signature;
    }
}