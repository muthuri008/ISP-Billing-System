<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MpesaPaymentService
{
    public function initiateStkPush(string $phone, float $amount, string $accountReference, string $description): array
    {
        $baseUrl = config('services.mpesa.base_url');
        if (! $baseUrl || ! config('services.mpesa.consumer_key') || ! config('services.mpesa.consumer_secret')) {
            throw new RuntimeException('M-Pesa is not configured.');
        }

        $token = $this->accessToken($baseUrl);
        $timestamp = now()->format('YmdHis');
        $password = base64_encode(config('services.mpesa.shortcode').config('services.mpesa.passkey').$timestamp);

        return Http::withToken($token)->post(rtrim($baseUrl, '/').'/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => config('services.mpesa.shortcode'),
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) round($amount),
            'PartyA' => preg_replace('/\D+/', '', $phone),
            'PartyB' => config('services.mpesa.shortcode'),
            'PhoneNumber' => preg_replace('/\D+/', '', $phone),
            'CallBackURL' => config('services.mpesa.callback_url'),
            'AccountReference' => $accountReference,
            'TransactionDesc' => $description,
        ])->throw()->json();
    }

    public function recordCallback(array $payload): ?Payment
    {
        $callback = data_get($payload, 'Body.stkCallback');
        if (! $callback) return null;
        $checkoutId = data_get($callback, 'CheckoutRequestID');
        if (! $checkoutId) return null;

        $payment = Payment::where('external_reference', $checkoutId)->first();
        if (! $payment) return null;

        if ((int) data_get($callback, 'ResultCode', 1) !== 0) {
            $payment->update(['status' => 'failed', 'metadata' => $payload]);
            return $payment->fresh();
        }

        $items = collect(data_get($callback, 'CallbackMetadata.Item', []))->keyBy('Name');
        $receipt = data_get($items->get('MpesaReceiptNumber'), 'Value');
        $amount = (float) data_get($items->get('Amount'), 'Value', $payment->amount);
        $payment->update(['status' => 'completed', 'amount' => $amount, 'transaction_reference' => $receipt, 'paid_at' => now(), 'metadata' => $payload]);
        return $payment->fresh();
    }

    private function accessToken(string $baseUrl): string
    {
        return Http::withBasicAuth(config('services.mpesa.consumer_key'), config('services.mpesa.consumer_secret'))
            ->get(rtrim($baseUrl, '/').'/oauth/v1/generate?grant_type=client_credentials')
            ->throw()->json('access_token');
    }
}
