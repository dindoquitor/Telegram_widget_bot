<?php
namespace TelegramWidget;

class ChatSession {
    private string $sessionId;
    private string $logDir;
    private array $state = [];
    private array $messages = [];

    public function __construct(string $sessionId) {
        // Sanitize session ID
        $this->sessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $sessionId);
        if (empty($this->sessionId)) {
            $this->sessionId = uniqid('sess_');
        }

        $dateStr = date('Y-m-d');
        $this->logDir = __DIR__ . '/../logs/' . $dateStr;
        
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }

        $this->load();
    }

    private function getFilePath(): string {
        return $this->logDir . '/' . $this->sessionId . '.json';
    }

    private function load(): void {
        $file = $this->getFilePath();
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data)) {
                $this->state = $data['state'] ?? ['human_mode' => false];
                $this->messages = $data['messages'] ?? [];
            }
        } else {
            $this->state = ['human_mode' => false];
            $this->messages = [];
        }
    }

    private function save(): void {
        $file = $this->getFilePath();
        $data = [
            'state' => $this->state,
            'messages' => $this->messages
        ];
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function addMessage(string $role, string $content): void {
        // role can be 'user', 'ai', 'human' (admin)
        $this->messages[] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => time()
        ];
        $this->save();
    }

    public function getMessages(): array {
        return $this->messages;
    }

    public function isHumanMode(): bool {
        return $this->state['human_mode'] ?? false;
    }

    public function setHumanMode(bool $isHuman): void {
        $this->state['human_mode'] = $isHuman;
        $this->save();
    }
    
    public function getSessionId(): string {
        return $this->sessionId;
    }
}
