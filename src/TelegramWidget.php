<?php
namespace TelegramWidget;

class TelegramWidget {
    private string $botToken;
    private string $adminChatId;

    public function __construct(Config $config) {
        $this->botToken = $config->get('TELEGRAM_BOT_TOKEN', '');
        $this->adminChatId = $config->get('ADMIN_CHAT_ID', '');
    }

    public function isConfigured(): bool {
        return !empty($this->botToken) && !empty($this->adminChatId);
    }

    public function sendMessage(string $text, ?string $replyToMessageId = null): ?array {
        if (!$this->isConfigured()) {
            return null;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        
        $data = [
            'chat_id' => $this->adminChatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        if ($replyToMessageId) {
            $data['reply_to_message_id'] = $replyToMessageId;
        }

        return $this->makeRequest($url, $data);
    }

    public function getUpdates(int $offset = 0): array {
        if (!$this->isConfigured()) {
            return [];
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/getUpdates";
        $data = ['offset' => $offset, 'timeout' => 0];
        
        $response = $this->makeRequest($url, $data);
        return $response['result'] ?? [];
    }

    private function makeRequest(string $url, array $data): ?array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            return json_decode($response, true);
        }
        return null;
    }
}
