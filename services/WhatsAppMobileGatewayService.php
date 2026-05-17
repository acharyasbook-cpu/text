<?php

declare(strict_types=1);

/**
 * Routes admin broadcasts to MacroDroid mobile webhook.
 * Trigger URL: …/acharyasbook?group={group_name}&message={message_body}
 */
final class WhatsAppMobileGatewayService
{
    public const WEBHOOK_BASE = 'https://trigger.macrodroid.com/9aad4101-7481-4873-bfb4-7a38dee2ad3a/acharyasbook';

    public function buildTriggerUrl(string $groupName, string $messageBody): string
    {
        $group = trim($groupName);
        $message = trim($messageBody);
        if ($group === '') {
            throw new InvalidArgumentException('group_name is required');
        }
        if ($message === '') {
            throw new InvalidArgumentException('message_body is required');
        }

        return self::WEBHOOK_BASE
            . '?group=' . rawurlencode($group)
            . '&message=' . rawurlencode($message);
    }

    /**
     * @return array{trigger_url:string,http_code:int,response_body:string,success:bool}
     */
    public function fireTrigger(string $groupName, string $messageBody, int $timeoutSeconds = 15): array
    {
        $url = $this->buildTriggerUrl($groupName, $messageBody);

        if (!function_exists('curl_init')) {
            return [
                'trigger_url' => $url,
                'http_code' => 0,
                'response_body' => '',
                'success' => false,
                'error' => 'cURL extension not available',
            ];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => max(5, $timeoutSeconds),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPGET => true,
            CURLOPT_USERAGENT => 'AcharyaBooks-WhatsAppMobileGateway/1.0',
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return [
                'trigger_url' => $url,
                'http_code' => $code,
                'response_body' => '',
                'success' => false,
                'error' => $err !== '' ? $err : 'Webhook request failed',
            ];
        }

        return [
            'trigger_url' => $url,
            'http_code' => $code,
            'response_body' => is_string($body) ? substr($body, 0, 2000) : '',
            'success' => $code >= 200 && $code < 400,
        ];
    }

    /**
     * Resolve display group name for a sub-course (Telugu title preferred).
     *
     * @param array<string,mixed> $subCourse
     */
    public function groupNameFromSubCourse(array $subCourse): string
    {
        $te = trim((string) ($subCourse['name_te'] ?? ''));
        $en = trim((string) ($subCourse['name'] ?? ''));
        $course = trim((string) ($subCourse['course_name'] ?? ''));
        $label = $te !== '' ? $te : $en;
        if ($label === '') {
            $label = 'Acharya Books Group';
        }
        if ($course !== '' && !str_contains($label, $course)) {
            return $course . ' · ' . $label;
        }

        return $label;
    }
}
