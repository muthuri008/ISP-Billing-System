<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('isp:about', function () {
    $this->info('Stellar Technologies ISP Billing System');
    $this->line('Billing, payments and network service lifecycle automation is installed.');
});

Schedule::command('billing:generate-invoices')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('billing:enforce-lifecycle --grace=3')
    ->dailyAt('00:20')
    ->withoutOverlapping()
    ->onOneServer();
