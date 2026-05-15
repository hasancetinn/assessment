<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;
use RuntimeException;

class WebhookService
{
    protected Client $client;
    protected string $webhookUrl;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => false,
        ]);

        $this->webhookUrl = config('services.webhook.url');
    }

    public function send(string $recipient, string $channel, string $content): array
    {
        $payload = [
            'to' => $recipient,
            'channel' => $channel,
            'content' => $content,
        ];

        try {
            $response = $this->client->post($this->webhookUrl, [
                'json' => $payload,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true) ?? [];

            if (in_array($statusCode, [200, 201, 202])) {
                return [
                    'messageId' => $body['messageId'] ?? Str::uuid()->toString(),
                    'status' => $body['status'] ?? 'accepted',
                    'timestamp' => $body['timestamp'] ?? now()->toIso8601String(),
                ];
            }

            throw new RuntimeException("Unexpected response: HTTP {$statusCode}");

        } catch (GuzzleException $e) {
            throw new RuntimeException("Webhook request failed: " . $e->getMessage());
        }
    }
}
