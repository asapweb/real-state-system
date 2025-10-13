<?php

return [
    'lqi' => [
        'post_issue' => env('FEATURE_LQI_POST_ISSUE', true),
        'require_rent_before_any_lqi' => env('FEATURE_LQI_REQUIRE_RENT_BEFORE_ANY_LQI', false),
    ],
];
