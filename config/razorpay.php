<?php

/**
 * Razorpay — set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in environment or below.
 * Without keys, freemium modal falls back to instant demo checkout (checkout.php).
 */
return [
    'key_id' => getenv('RAZORPAY_KEY_ID') ?: '',
    'key_secret' => getenv('RAZORPAY_KEY_SECRET') ?: '',
    'currency' => 'INR',
];
