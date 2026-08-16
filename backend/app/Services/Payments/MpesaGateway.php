<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MpesaGateway
{
    public function accessToken(): string
    {
        $key=config('services.mpesa.consumer_key');
        $secret=config('services.mpesa.consumer_secret');
        $base=rtrim(config('services.mpesa.base_url'),'/' );
        if(!$key || !$secret || !$base) throw new RuntimeException('M-Pesa gateway is not configured.');
        $response=Http::withBasicAuth($key,$secret)->acceptJson()->get($base.'/oauth/v1/generate?grant_type=client_credentials');
        $response->throw();
        return (string)$response->json('access_token');
    }

    public function stkPush(string $phone, float $amount, string $accountReference, string $description): array
    {
        $token=$this->accessToken();
        $timestamp=now()->format('YmdHis');
        $shortcode=config('services.mpesa.shortcode');
        $passkey=config('services.mpesa.passkey');
        $base=rtrim(config('services.mpesa.base_url'),'/');
        $callback=config('services.mpesa.callback_url');
        if(!$shortcode || !$passkey || !$callback) throw new RuntimeException('M-Pesa STK configuration is incomplete.');
        $password=base64_encode($shortcode.$passkey.$timestamp);
        $payload=['BusinessShortCode'=>$shortcode,'Password'=>$password,'Timestamp'=>$timestamp,'TransactionType'=>config('services.mpesa.transaction_type','CustomerPayBillOnline'),'Amount'=>max(1,(int)round($amount)),'PartyA'=>$phone,'PartyB'=>$shortcode,'PhoneNumber'=>$phone,'CallBackURL'=>$callback,'AccountReference'=>$accountReference,'TransactionDesc'=>$description];
        $response=Http::withToken($token)->acceptJson()->post($base.'/mpesa/stkpush/v1/processrequest',$payload);
        $response->throw();
        return $response->json();
    }
}
