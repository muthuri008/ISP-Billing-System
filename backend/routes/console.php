<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('isp:about', function () {
    $this->info('ISP Billing System');
    $this->line('Backend foundation is installed.');
});
