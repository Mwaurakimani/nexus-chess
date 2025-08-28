<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    // Initiate deposit to Onit
    public function deposit(Request $request): JsonResponse
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

        $callbackUrl = route('onit.deposit.callback', [], true);
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

    public function withdraw(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'destination' => 'required|string', // the receiving account (e.g., phone number)
            'source' => 'required|string',      // your source account on Onit
        ]);

        $reference = 'ONIT-WD-' . strtoupper(uniqid());

        // Create local record
        $transaction = Transaction::create([
            'user_id' => User::where('email','kimmwaus@gmail.com')->firstOrFail()->id,
            'onit_reference' => $reference,
            'amount' => $request->amount,
            'status' => 'pending',
            'channel' => 'MPESA',
            'narration' => $request->narration ?? 'Withdrawal via Onit to account ID:' . (User::where('email','kimmwaus@gmail.com')->firstOrFail()->id),
        ]);

        $bearer_token = $this->authenticate();

        $callbackUrl = route('onit.withdrawal.callback', [], true);
        $callbackUrl = preg_replace("/^http:/i", "https:", $callbackUrl);

        // Call Onit Withdraw API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $bearer_token,
        ])->post(config('services.onit.base_url') . '/transaction/withdraw', [
            'originatorRequestId' => $reference,
            'sourceAccount' => $request->source,              // your source account number
            'destinationAccount' => $request->destination,    // target MPESA number
            'amount' => $request->amount,
            'channel' => 'MPESA',
            'channelType' => 'MOBILE',
            'product' => 'CA04',
            'narration' => $transaction->narration,
            'callbackUrl' => $callbackUrl,
        ]);

        return response()->json([
            'transaction' => $transaction,
            'onit_response' => $response->json(),
        ]);
    }

    // Handle Onit callback webhook
    public function deposit_callback(Request $request): void
    {
        Log::info('Onit callback received:', $request->all());
    }
    public function withdrawal_callback(Request $request): void
    {
        Log::info('Onit callback received:', $request->all());
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
