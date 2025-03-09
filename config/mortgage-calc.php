<?php

return [

    // Configures the "api" calculation provider method.
    'api' => [
        // The base url of the calculation api (without the version number).
        'url' => env('MORTGAGE_CALC_API_URL', 'https://api.hypotheekbond.nl/calculation'),
        // The api key used to authenticate to the calculation api.
        'api_key' => env('MORTGAGE_CALC_API_KEY'),
    ],

    // Configures the defaults for the inputs of the calculations.
    'defaults' => [
        // Configures the defaults for the input of the maximum-by-income calculation.
        'maximum_by_income' => [
            'duration_in_months' => 360,
            'interest_percentage' => '1.501'
        ],
        // Configures the defaults for the input of the maximum-by-value calculation.
        'maximum_by_value' => [],
    ],

    'ini_header' =>  <<<'INI'
            ;; ================================================================================ ;;
            ;;                            _   _               _    _                     _      ;;
            ;;    /\  /\_   _ _ __   ___ | |_| |__   ___  ___| | _| |__   ___  _ __   __| |     ;;
            ;;   / /_/ / | | | '_ \ / _ \| __| '_ \ / _ \/ _ \ |/ / '_ \ / _ \| '_ \ / _` |     ;;
            ;;  / __  /| |_| | |_) | (_) | |_| | | |  __/  __/   <| |_) | (_) | | | | (_| |     ;;
            ;;  \/ /_/  \__, | .__/ \___/ \__|_| |_|\___|\___|_|\_\_.__/ \___/|_| |_|\__,_|     ;;
            ;;          |___/|_|                   (Technische assessment van Roel Hemerik)     ;;
            ;; ================================================================================ ;;
            INI,

];
