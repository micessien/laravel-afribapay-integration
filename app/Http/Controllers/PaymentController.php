<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AfribapayAccessToken;
use App\Models\Payment;
use Carbon\Carbon;

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
     * Payment callback
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function paymentCallback(Request $request)
    {
        $data = null;
        $error = null;
        // Get transaction id
        $transaction_id = $request->transaction_id ?? null;
        
        // Verify payment
        $response = $this->payment_verification($transaction_id);
        if (isset($response->data->status)) {
            $payment = Payment::where('transaction_id', $transaction_id)->first();
            if ($payment) {
                $payment->status = $response->data->status;
                $payment->save();
            }
            // Set data to be return
            $data = $response->data;
        }else{
            // Set error message to be return
            $error = 'Somthing went wrong';
            // When request have an error
            if (property_exists($response, 'error')) {
                $errorMessage = $response->error->message ?? 'Somthing went wrong';
                $error = $errorMessage;
            }
        }
        
        return view('pay.callback', compact('data', 'error'));
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

        // Make Payment
        $secureRandom = random_int(1000000000, 9999999999); // 10-digit number
        $formData = [
            "country" => $request->country,
            "operator" => $request->operator,
            "phone_number" => $request->phone,
            "amount" => $request->amount,
            "currency" => "XOF",
            "order_id" => "order-".$secureRandom,
            "merchant_key" => env('AFRIBAPAY_API_MARCHANDKEY'),
            "reference_id" => "ref-cievent-cnjci",
            "lang" => "fr",
            "return_url" => "http://localhost:8000",
            "cancel_url" => "http://localhost:8000/cancel",
            // "notify_url" => "https://localhost:8000/notification_ipn_webhook",
        ];
        $pay = $this->initialize_payment($formData);
        if ($pay) {
            // When request is successful
            if (isset($pay->data->status)) {
                // Save Payment
                $payment = new Payment;
                $payment->transaction_id = $pay->data->transaction_id;
                $payment->phone = $pay->data->phone_number;
                $payment->status = $pay->data->status;
                $payment->response = json_encode($pay);
                $payment->save();

                // Return message when it has failed
                if ($pay->data->status === "FAILED") {
                    return back()->withError('Transaction has failed');
                }

                // Continue When is successful
                dd($pay);
            }else {
                // When request have an error
                if (property_exists($pay, 'error')) {
                    $errorMessage = $pay->error->message ?? 'Somthing went wrong';
                    return back()->withError($errorMessage);
                }
                dd($pay);
                return back()->withError('Somthing went wrong');
            }
        }else{
            return back()->withError('Somthing went wrong');
        }
    }

    public function initialize_payment($formData)
    {
        $url = env("AFRIBAPAY_API_URL")."/v1/pay/payin";
        // Get Access Token
        $token = $this->get_accesstoken();

        $fields_string = json_encode($formData);
        // dd($fields_string);
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

    /**
     * Payment verification
     *
     * @return \Illuminate\Response $response
     */
    public function payment_verification($transaction_id)
    {
        $url = env("AFRIBAPAY_API_URL")."/v1/status?transaction_id=".$transaction_id;
        // Get Access Token
        $token = $this->get_accesstoken();

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

}