<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TelegramService
{
    private string $botToken;
    private string $chatId;
    private HttpClientInterface $client;

    public function __construct(string $botToken, string $chatId, HttpClientInterface $client)
    {
        $this->botToken = $botToken;
        $this->chatId = $chatId;
        $this->client = $client;
    }

    public function sendMessage(string $text): void
    {
        $url = sprintf('https://api.telegram.org/bot%s/sendMessage', $this->botToken);

        $this->client->request('POST', $url, [
            'body' => [
                'chat_id' => $this->chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ],
        ]);
    }

    public function sendDocument(string $filePath, ?string $caption = null): void
    {
        $url = 'https://api.telegram.org/bot' . $this->botToken . '/sendDocument';

        $postFields = [
            'chat_id' => $this->chatId,
            'document' => new \CURLFile($filePath),
        ];

        if ($caption) {
            $postFields['caption'] = $caption;
            $postFields['parse_mode'] = 'HTML';
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
        ]);

        curl_exec($ch);
        curl_close($ch);
    }
}
