<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Parent Access (Code Simplifié)
    |--------------------------------------------------------------------------
    */

    'parent_access' => [
        'max_devices' => env('PARENT_ACCESS_MAX_DEVICES', 5),
        'code_validity_minutes' => env('PARENT_ACCESS_CODE_VALIDITY_MINUTES', 60),
    ],

];
