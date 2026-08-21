<?php

function get_subscription_plan_options(): array
{
    return [
        '6h_monthly' => [
            'label' => '6 Hour Plan',
            'plan_name' => '1 Month Plan',
            'seat_type' => '6h',
            'price' => 450.00,
            'duration_months' => 1,
            'bonus_days' => 0,
            'renewal_type' => 'normal'
        ],
        '12h_monthly' => [
            'label' => '12 Hour Plan',
            'plan_name' => '1 Month Plan',
            'seat_type' => '12h',
            'price' => 800.00,
            'duration_months' => 1,
            'bonus_days' => 0,
            'renewal_type' => 'normal'
        ],
        '24h_monthly' => [
            'label' => '24 Hour Plan',
            'plan_name' => '1 Month Plan',
            'seat_type' => '24h',
            'price' => 1000.00,
            'duration_months' => 1,
            'bonus_days' => 0,
            'renewal_type' => 'normal'
        ],
        'premium_3m' => [
            'label' => '3 Month Premium (6 Hour)',
            'plan_name' => '3 Month Premium',
            'seat_type' => '6h',
            'price' => 2500.00,
            'duration_months' => 3,
            'bonus_days' => 7,
            'renewal_type' => 'bulk_3month'
        ]
    ];
}

function get_default_plan_key_for_amount(float $amount): string
{
    $plans = get_subscription_plan_options();
    foreach ($plans as $key => $plan) {
        if (abs($amount - (float)$plan['price']) < 0.01) {
            return $key;
        }
    }
    return '6h_monthly';
}
?>
