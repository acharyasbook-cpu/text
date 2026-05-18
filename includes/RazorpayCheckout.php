<?php

declare(strict_types=1);

final class RazorpayCheckout
{
    /** @return array{key_id:string,key_secret:string,currency:string} */
    public static function config(): array
    {
        $file = dirname(__DIR__) . '/config/razorpay.php';
        $cfg = is_file($file) ? (require $file) : [];

        return [
            'key_id' => trim((string) ($cfg['key_id'] ?? getenv('RAZORPAY_KEY_ID') ?: '')),
            'key_secret' => trim((string) ($cfg['key_secret'] ?? getenv('RAZORPAY_KEY_SECRET') ?: '')),
            'currency' => (string) ($cfg['currency'] ?? 'INR'),
        ];
    }

    public static function isConfigured(): bool
    {
        $c = self::config();

        return $c['key_id'] !== '' && $c['key_secret'] !== '';
    }

    /**
     * @return array{ok:bool,order_id?:string,amount?:int,currency?:string,key_id?:string,description?:string,error?:string,fallback?:bool}
     */
    public static function createOrderForPlan(array $plan, int $userId, ?float $amountInrOverride = null): array
    {
        if (!self::isConfigured()) {
            return ['ok' => false, 'fallback' => true, 'error' => 'Razorpay not configured'];
        }

        $cfg = self::config();
        $base = (float) ($plan['price_inr'] ?? 0);
        $charge = $amountInrOverride !== null ? (float) $amountInrOverride : $base;
        if ($charge < 0) {
            $charge = 0.0;
        }
        $amount = (int) round($charge * 100);
        if ($amount < 100) {
            $amount = 100;
        }

        $receipt = 'ab_' . $userId . '_' . (int) ($plan['id'] ?? 0) . '_' . time();
        $payload = json_encode([
            'amount' => $amount,
            'currency' => $cfg['currency'],
            'receipt' => $receipt,
            'notes' => [
                'plan_id' => (string) ($plan['id'] ?? ''),
                'user_id' => (string) $userId,
            ],
        ], JSON_THROW_ON_ERROR);

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_USERPWD => $cfg['key_id'] . ':' . $cfg['key_secret'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $code < 200 || $code >= 300) {
            return ['ok' => false, 'fallback' => true, 'error' => 'Could not create Razorpay order'];
        }

        $data = json_decode((string) $raw, true);
        if (!is_array($data) || empty($data['id'])) {
            return ['ok' => false, 'fallback' => true, 'error' => 'Invalid Razorpay response'];
        }

        return [
            'ok' => true,
            'order_id' => (string) $data['id'],
            'amount' => (int) ($data['amount'] ?? $amount),
            'currency' => (string) ($data['currency'] ?? $cfg['currency']),
            'key_id' => $cfg['key_id'],
            'description' => (string) ($plan['label'] ?? 'Acharya Books'),
        ];
    }

    public static function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        $secret = self::config()['key_secret'];
        if ($secret === '' || $orderId === '' || $paymentId === '' || $signature === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $secret);

        return hash_equals($expected, $signature);
    }
}
