<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Services\AfribapayService;

class WebhookController extends Controller
{   
    protected $afribapay;

    public function __construct(AfribapayService $afribapay)
    {
        $this->afribapay = $afribapay;
    }

    /**
     * Handle the incoming webhook notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // Handle webhook notifications from AfribaPAY
    // This method processes the webhook notification sent by AfribaPAY
    // It verifies the signature, decodes the JSON payload, and updates the payment status accordingly
    // The method returns a JSON response indicating the success or failure of the operation
    // @throws \Exception
    // @return \Illuminate\Http\JsonResponse
    public function handle(Request $request)
    {
        try {
            // Process webhook payload & Decode the JSON payload into a PHP associative array
            $payload = $request->getContent();
            $data = json_decode($payload, true);
            // Check if the payload was successfully decoded
            if ($data === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid JSON received.',
                ], 400);
            }
            // START Signature Verification
            // Retrieve AfribaPAY signature from headers
            $afribaPaySignature = $request->header('Afribapay-Sign') ?? null;
            // Sign the payload using the merchant key
            $computedSignature = $this->afribapay->afribapay_sign($payload, config("services.afribapay.key"));
            // Compare the signature received with the computed one
            if (!hash_equals($computedSignature, $afribaPaySignature)) {
                // Signature mismatch, possibly indicating that the request did not originate from AfribaPAY
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook.',
                ], 403);
            }
            // END Signature Verification
            
            // Extract important data from the decoded payload
            $transactionId = $data['transaction_id'] ?? null;
            $status = $data['status'] ?? null;
            // Process the payment status
            if ($status === 'SUCCESS') {
                // Handle successful payment
                $payment = Payment::where('transaction_id', $transactionId)->first();
                if ($payment) {
                    $payment->status = 'SUCCESS';
                    $payment->save();
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Transaction successful.',
                ], 200);
            } elseif ($status === 'FAILED') {
                // Handle failed payment logic
                $payment = Payment::where('transaction_id', $transactionId)->first();
                if ($payment) {
                    $payment->status = 'FAILED';
                    $payment->save();
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Transaction failed.',
                ], 200);
            }else{
                // Handle any unexpected statuses
                return response()->json([
                    'success' => true,
                    'message' => 'Unknown status.',
                    'status' => $status,
                ], 400);
            }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the webhook.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
    
}