<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Services\AfribapayService;

class PaymentController extends Controller
{
    protected $afribapay;

    public function __construct(AfribapayService $afribapay)
    {
        $this->afribapay = $afribapay;
    }

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
        $response = $this->afribapay->paymentVerification($transaction_id);
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
            $error = 'Something went wrong';
            // When request have an error
            if (property_exists($response, 'error')) {
                $errorMessage = $response->error->message ?? 'Something went wrong';
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
            "reference_id" => "ref-miky-dev",
            "lang" => "fr",
            "return_url" => "http://localhost:8000",
            "cancel_url" => "http://localhost:8000/cancel",
            // "notify_url" => "https://localhost:8000/notification_ipn_webhook",
        ];
        $pay = $this->afribapay->initializePayment($formData);
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
                return redirect()->route('pay.callback', ['transaction_id' => $pay->data->transaction_id]);
            }else {
                // When request have an error
                if (property_exists($pay, 'error')) {
                    $errorMessage = $pay->error->message ?? 'Something went wrong';
                    return back()->withError($errorMessage);
                }
                dd($pay);
                return back()->withError('Something went wrong');
            }
        }else{
            return back()->withError('Something went wrong');
        }
    }
}