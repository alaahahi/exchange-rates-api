<?php

return [
    'cache_ttl' => (int) env('EXCHANGE_RATES_CACHE_TTL', 120),
    'gold_cache_ttl' => (int) env('GOLD_RATES_CACHE_TTL', env('EXCHANGE_RATES_CACHE_TTL', 120)),

    'market_timezone' => env('MARKET_TIMEZONE', 'Asia/Baghdad'),
    'market_open_hour' => (int) env('MARKET_OPEN_HOUR', 9),
    'market_close_hour' => (int) env('MARKET_CLOSE_HOUR', 17),
    'market_open_days' => array_map(
        'intval',
        explode(',', (string) env('MARKET_OPEN_DAYS', '0,1,2,3,4,6')),
    ),

    /*
    |--------------------------------------------------------------------------
    | Live rate source
    |--------------------------------------------------------------------------
    | Default: Dinarlive per-governorate board (Erbil). Other providers remain
    | available as fallbacks.
    */
    'live' => [
        'enabled' => filter_var(env('EXCHANGE_LIVE_ENABLED', true), FILTER_VALIDATE_BOOL),
        'provider' => env('EXCHANGE_RATE_PROVIDER', 'dinarlive'),
        'location' => env('EXCHANGE_LOCATION', 'erbil'),
        'sync_ttl' => (int) env('EXCHANGE_LIVE_SYNC_TTL', 120),
        'http_timeout' => (int) env('EXCHANGE_LIVE_HTTP_TIMEOUT', 12),
        'quote_unit' => (int) env('EXCHANGE_QUOTE_UNIT', 100),
        'buy_spread_percent' => (float) env('EXCHANGE_BUY_SPREAD_PERCENT', 0),
        'sell_spread_percent' => (float) env('EXCHANGE_SELL_SPREAD_PERCENT', 0),
        // Absolute IQD gap (sell - buy) on the per-100 board when the provider
        // returns a single mid price (Dinarlive). Shipping Qamar boards ~150.
        'board_spread_iqd' => (float) env('EXCHANGE_BOARD_SPREAD_IQD', 150),
        'source_label' => env('EXCHANGE_SOURCE_LABEL', 'Dinarlive — أربيل'),
        'priority' => [
            'USD' => 1,
            'EUR' => 2,
            'GBP' => 3,
            'TRY' => 4,
            'AED' => 5,
            'SAR' => 6,
            'KWD' => 7,
            'QAR' => 8,
            'JOD' => 9,
            'EGP' => 10,
            'CHF' => 11,
            'CAD' => 12,
            'AUD' => 13,
            'JPY' => 14,
            'CNY' => 15,
            'INR' => 16,
            'IRR' => 17,
            'NOK' => 18,
            'SEK' => 19,
            'DKK' => 20,
        ],
    ],

    'providers' => [
        'dinarlive' => [
            'base_url' => env('DINARLIVE_URL', 'https://dinarlive.com/api/v2/get-price'),
            'cross_rates_url' => env('OPEN_ER_API_URL', 'https://open.er-api.com/v6/latest/USD'),
            'source_key' => 'dinarlive.com',
            'source_label' => env('EXCHANGE_SOURCE_LABEL', 'Dinarlive'),
            'default_location' => env('EXCHANGE_LOCATION', 'erbil'),
            // Ignore Dinarlive quotes older than this (Baghdad/others are often months stale).
            'max_age_days' => (int) env('DINARLIVE_MAX_AGE_DAYS', 2),
            'locations' => [
                'erbil' => 'أربيل',
                'baghdad' => 'بغداد',
                'sulaymaniah' => 'السليمانية',
                'duhok' => 'دهوك',
                'kirkuk' => 'كركوك',
            ],
        ],
        'iraqsm' => [
            'fx_url' => env('IRAQSM_FX_URL', 'https://iraqsm.com/data/fx.json'),
            'cross_rates_url' => env('OPEN_ER_API_URL', 'https://open.er-api.com/v6/latest/USD'),
            'source_key' => 'iraqsm.com',
            'source_label' => 'سوق بغداد الموازي (iraqsm)',
        ],
        'qamar' => [
            'base_url' => env('QAMAR_RATES_URL', 'https://qamaralfajr.com/production/exchange_rates.php'),
            'source_key' => 'qamaralfajr.com',
            'source_label' => 'قمر الفجر للصيرفة',
        ],
        'open_er_api' => [
            'base_url' => env('OPEN_ER_API_URL', 'https://open.er-api.com/v6/latest/USD'),
            'source_key' => 'open.er-api.com',
            'source_label' => 'ExchangeRate-API (open.er-api.com)',
        ],
    ],

    // Arabic display names (fallback = currency code).
    'currency_names' => [
        'USD' => 'دولار أمريكي',
        'EUR' => 'يورو',
        'GBP' => 'جنيه إسترليني',
        'TRY' => 'ليرة تركية',
        'AED' => 'درهم إماراتي',
        'SAR' => 'ريال سعودي',
        'KWD' => 'دينار كويتي',
        'QAR' => 'ريال قطري',
        'JOD' => 'دينار أردني',
        'EGP' => 'جنيه مصري',
        'CHF' => 'فرنك سويسري',
        'CAD' => 'دولار كندي',
        'AUD' => 'دولار أسترالي',
        'JPY' => 'ين ياباني',
        'CNY' => 'يوان صيني',
        'INR' => 'روبية هندية',
        'IRR' => 'ريال إيراني',
        'OMR' => 'ريال عماني',
        'BHD' => 'دينار بحريني',
        'SYP' => 'ليرة سورية',
        'LBP' => 'ليرة لبنانية',
        'YER' => 'ريال يمني',
        'RUB' => 'روبل روسي',
        'SEK' => 'كرونة سويدية',
        'NOK' => 'كرونة نرويجية',
        'DKK' => 'كرونة دنماركية',
        'NZD' => 'دولار نيوزيلندي',
        'ZAR' => 'راند جنوب أفريقي',
        'BRL' => 'ريال برازيلي',
        'MXN' => 'بيزو مكسيكي',
        'HKD' => 'دولار هونغ كونغ',
        'SGD' => 'دولار سنغافوري',
        'KRW' => 'وون كوري',
        'THB' => 'بات تايلندي',
        'MYR' => 'رينغيت ماليزي',
        'PKR' => 'روبية باكستانية',
        'AFN' => 'أفغاني',
    ],
];
