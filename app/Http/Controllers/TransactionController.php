<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    // Initiate deposit to Onit
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'source' => 'required|string',
        ]);

        $reference = 'ONIT-' . strtoupper(uniqid());

        // Create local record
        $transaction = Transaction::create([
            'user_id' => User::where('email','kimmwaus@gmail.com')->firstOrFail()->id,
            'onit_reference' => $reference,
            'amount' => $request->amount,
            'status' => 'pending',
            'channel' => $request->source,
            'narration' => $request->narration ?? 'Deposit via Onit to account ID:'.(User::where('email','kimmwaus@gmail.com')->firstOrFail()->id),
        ]);

        $bearer_token = $this->authenticate();

        $callbackUrl = route('onit.callback', [], true);
        $callbackUrl = preg_replace("/^http:/i", "https:", $callbackUrl);

        // Call Onit API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $bearer_token ,
        ])->post(config('services.onit.base_url') . '/transaction/deposit', [
            'originatorRequestId' => $reference,
            'destinationAccount' => config('services.onit.account'),
            'sourceAccount' => $request->source,
            'amount' => $request->amount,
            'channel' => 'MPESA',
            'product' => 'CA05',
            'event' => '',
            'narration' => $transaction->narration,
            'callbackUrl' => $callbackUrl,
        ]);

        return response()->json([
            'transaction' => $transaction,
            'onit_response' => $response->json(),
        ]);
    }

    // Handle Onit callback webhook
    public function callback(Request $request)
    {
        Log::info('Onit callback received:', $request->all());

        if(false){
            $transaction = Transaction::where('onit_reference', $request->originatorRequestId)->first();

            if ($transaction) {
                $transaction->update([
                    'status' => $request->status === 'SUCCESS' ? 'successful' : 'failed'
                ]);
            }

            return response()->json(['message' => 'Callback processed']);
        }
    }

    public function authenticate(): ?string
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(config('services.onit.base_url') . '/auth/jwt', [
            'userId'   => config('services.onit.user_id'),
            'password' => config('services.onit.password'),
        ]);

        if ($response->successful()) {
            return $response->json()['access_token'] ?? null;
        }

        return null;
    }
}
