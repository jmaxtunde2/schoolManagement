<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\SmsServiceProvider;
use App\Providers\WebPushServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    SmsServiceProvider::class,
    WebPushServiceProvider::class,
];
