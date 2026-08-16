<?php

namespace App\Services\Customer;

use App\Models\Customer;
use Illuminate\Support\Str;

class CustomerNumberService
{
    public function generate(): string
    {
        do {
            $number = 'CUS-'.now()->format('Ym').'-'.strtoupper(Str::random(6));
        } while (Customer::where('customer_number', $number)->exists());

        return $number;
    }
}
