<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MpesaGateway
{
    public function accessToken(): string
    {
        $key = config('services.mpesa.consumer_key');
        $secret = config('services.mpesa.consumer_secret');
        $base = rtrim((string) config('services.mpesa.base_url'), '/');

        if (! $key || ! $secret || ! $base) {
            throw new RuntimeException('M-Pesa gateway is not configured.');
        }

        $response = Http::withBasicAuth($key, $secret)
            ->acceptJson()
            ->get($base.'/oauth/v1/generate?grant_type=client_credentials');

        $response->throw();
        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('M-Pesa did not return an access token.');
        }

        return $token;
    }

    public function stkPush(string $phone, float $amount, string $accountReference, string $description): array
    {
        $token = $this->accessToken();
        $timestamp = now()->format('YmdHis');
        $shortcode = config('services.mpesa.shortcode');
        $passkey = config('services.mpesa.passkey');
        $base = rtrim((string) config('services.mpesa.base_url'), '/');
        $callback = config('services.mpesa.callback_url');

        if (! $shortcode || ! $passkey || ! $callback) {
            throw new RuntimeException('M-Pesa STK configuration is incomplete.');
        }

        $phone = $this->normalizeKenyanPhone($phone);
        $amount = (int) round($amount);

        if ($amount < 1) {
            throw new RuntimeException('M-Pesa STK amount must be at least KES 1.');
        }

        $password = base64_encode($shortcode.$passkey.$timestamp);
        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => config('services.mpesa.transaction_type', 'CustomerPayBillOnline'),
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $callback,
            'AccountReference' => substr($accountReference, 0, 12),
            'TransactionDesc' => substr($description, 0, 13),
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($base.'/mpesa/stkpush/v1/processrequest', $payload);

        $response->throw();
        $result = $response->json();

        if (! is_array($result) || empty($result['CheckoutRequestID'])) {
            throw new RuntimeException('M-Pesa did not return a valid CheckoutRequestID.');
        }

        return $result;
    }

    public function queryStkPush(string $checkoutRequestId): array
    {
        $token = $this->accessToken();
        $timestamp = now()->format('YmdHis');
        $shortcode = config('services.mpesa.shortcode');
        $passkey = config('services.mpesa.passkey');
        $base = rtrim((string) config('services.mpesa.base_url'), '/');

        if (! $shortcode || ! $passkey || ! $checkoutRequestId) {
            throw new RuntimeException('M-Pesa STK query configuration is incomplete.');
        }

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => base64_encode($shortcode.$passkey.$timestamp),
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($base.'/mpesa/stkpushquery/v1/query', $payload);

        $response->throw();
        $result = $response->json();

        if (! is_array($result) || ! array_key_exists('ResultCode', $result)) {
            throw new RuntimeException('Safaricom did not return a verifiable STK transaction status.');
        }

        return $result;
    }

    private function normalizeKenyanPhone(string $phone): string
    {
        $phone = preg_replace('/^\+/', '', trim($phone)) ?? '';

        if (preg_match('/^0([17]\d{8})$/', $phone, $matches)) {
            return '254'.$matches[1];
        }

        if (preg_match('/^([17]\d{8})$/', $phone, $matches)) {
            return '254'.$matches[1];
        }

        if (preg_match('/^254[17]\d{8}$/', $phone)) {
            return $phone;
        }

        throw new RuntimeException('Enter a valid Kenyan mobile number, for example 0712345678 or 254712345678.');
    }
}
