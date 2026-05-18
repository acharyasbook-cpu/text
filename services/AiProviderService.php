<?php

declare(strict_types=1);

/** Multi-provider AI completion (OpenAI, Anthropic, Gemini). */
final class AiProviderService
{
    /**
     * @param array{provider:string,model_name:string,api_key:string} $slot
     */
    public function complete(array $slot, string $systemPrompt, string $userPrompt): string
    {
        $provider = strtolower((string) ($slot['provider'] ?? 'openai'));
        $key = trim((string) ($slot['api_key'] ?? ''));
        if ($key === '') {
            throw new RuntimeException('No API key configured for active slot');
        }
        $model = (string) ($slot['model_name'] ?? 'gpt-4o-mini');

        return match ($provider) {
            'anthropic', 'claude' => $this->anthropic($key, $model, $systemPrompt, $userPrompt),
            'gemini', 'google' => $this->gemini($key, $model, $systemPrompt, $userPrompt),
            default => $this->openai($key, $model, $systemPrompt, $userPrompt),
        };
    }

    private function openai(string $key, string $model, string $system, string $user): string
    {
        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0.4,
        ];
        $json = $this->httpJson(
            'https://api.openai.com/v1/chat/completions',
            $body,
            ['Authorization: Bearer ' . $key, 'Content-Type: application/json']
        );
        $content = $json['choices'][0]['message']['content'] ?? '';

        return is_string($content) ? $content : '';
    }

    private function anthropic(string $key, string $model, string $system, string $user): string
    {
        $body = [
            'model' => $model,
            'max_tokens' => 4096,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $user]],
        ];
        $json = $this->httpJson(
            'https://api.anthropic.com/v1/messages',
            $body,
            [
                'x-api-key: ' . $key,
                'anthropic-version: 2023-06-01',
                'Content-Type: application/json',
            ]
        );
        foreach ($json['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                return (string) ($block['text'] ?? '');
            }
        }

        return '';
    }

    private function gemini(string $key, string $model, string $system, string $user): string
    {
        $modelPath = rawurlencode($model ?: 'gemini-1.5-flash');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelPath}:generateContent?key=" . rawurlencode($key);
        $body = [
            'contents' => [
                ['parts' => [['text' => $system . "\n\n" . $user]]],
            ],
        ];
        $json = $this->httpJson($url, $body, ['Content-Type: application/json']);
        $parts = $json['candidates'][0]['content']['parts'] ?? [];

        return (string) ($parts[0]['text'] ?? '');
    }

    /** @param array<string,mixed> $body */
    private function httpJson(string $url, array $body, array $headers): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('curl init failed');
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 120,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            throw new RuntimeException('AI request failed: ' . $err);
        }
        $decoded = json_decode($raw, true);
        if ($code >= 400) {
            $msg = is_array($decoded) ? ($decoded['error']['message'] ?? $raw) : $raw;
            throw new RuntimeException('AI HTTP ' . $code . ': ' . (is_string($msg) ? $msg : json_encode($msg)));
        }

        return is_array($decoded) ? $decoded : [];
    }
}
