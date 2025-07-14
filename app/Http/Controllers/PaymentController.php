<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        dd($request);
    }

    public function generate_accesstoken()
    {
        
    }
}