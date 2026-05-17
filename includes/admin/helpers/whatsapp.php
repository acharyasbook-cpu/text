<?php

declare(strict_types=1);

/**
 * Build WhatsApp click-to-chat URL for admin student outreach.
 */
function admin_whatsapp_chat_url(string $phone, string $studentName, ?string $templateKey = 'welcome'): string
{
    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === '') {
        return '';
    }
    if (strlen($digits) === 10) {
        $digits = '91' . $digits;
    }

    $name = trim($studentName) !== '' ? trim($studentName) : 'విద్యార్థి';
    $templates = [
        'welcome' => "హలో {$name}, ఆచార్య బుక్స్ ప్లాట్‌ఫామ్‌కు స్వాగతం! మీ అభ్యాస ప్రయాణానికి మేము సహాయం చేస్తాము.",
        'reminder' => "హలో {$name}, ఆచార్య బుక్స్ నుండి సందేశం — దయచేసి ఈ రోజు మీ షెడ్యూల్ టెస్ట్ పూర్తి చేయండి.",
        'payment' => "హలో {$name}, మీ ఆచార్య బుక్స్ సబ్‌స్క్రిప్షన్ స్థితి గురించి సంప్రదించడానికి ఈ సందేశం.",
    ];
    $text = $templates[$templateKey] ?? $templates['welcome'];

    return 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
}
