<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('isp:about', function () {
    $this->info('ISP Billing System');
    $this->line('Backend foundation is installed.');
});

Schedule::command('billing:generate-invoices')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->onOneServer();
