<?php
namespace TelegramWidget;

class AiAgent {
    private string $provider;
    private string $apiKey;

    private string $systemPrompt = "You are a helpful customer support agent for our website. If you do not know the answer to a question, or if the user explicitly asks to speak to a human or live agent, you must output the exact string: [HANDOFF_REQUIRED] and nothing else.";

    public function __construct(Config $config) {
        $this->provider = strtolower($config->get('AI_PROVIDER', 'openai'));
        $this->apiKey = $config->get('AI_API_KEY', '');
    }

    public function generateReply(ChatSession $session): string {
        if (empty($this->apiKey)) {
            return "[HANDOFF_REQUIRED]"; // Fallback if AI is not configured
        }

        $messages = $session->getMessages();
        
        $apiMessages = [
            ['role' => 'system', 'content' => $this->systemPrompt]
        ];

        foreach ($messages as $msg) {
            // map internal roles to AI roles (human => assistant since human is acting on behalf of the company)
            $role = ($msg['role'] === 'user') ? 'user' : 'assistant';
            $apiMessages[] = ['role' => $role, 'content' => $msg['content']];
        }

        if (in_array($this->provider, ['openai', 'deepseek', 'chatgpt'])) {
            return $this->callOpenAiCompatible($apiMessages);
        } elseif ($this->provider === 'claude') {
            return $this->callAnthropic($apiMessages);
        }

        return "[HANDOFF_REQUIRED]";
    }

    private function callOpenAiCompatible(array $messages): string {
        $url = "https://api.openai.com/v1/chat/completions";
        $model = "gpt-3.5-turbo";

        if ($this->provider === 'deepseek') {
            $url = "https://api.deepseek.com/chat/completions";
            $model = "deepseek-chat";
        }

        $data = [
            "model" => $model,
            "messages" => $messages,
            "temperature" => 0.7
        ];

        return $this->makeHttpRequest($url, $data, [
            "Authorization: Bearer " . $this->apiKey,
            "Content-Type: application/json"
        ]);
    }

    private function callAnthropic(array $messages): string {
        $url = "https://api.anthropic.com/v1/messages";
        
        // Anthropic system prompt is separate
        $system = $messages[0]['content'];
        array_shift($messages);

        $data = [
            "model" => "claude-3-haiku-20240307",
            "max_tokens" => 1024,
            "system" => $system,
            "messages" => $messages
        ];

        $response = $this->makeHttpRequest($url, $data, [
            "x-api-key: " . $this->apiKey,
            "anthropic-version: 2023-06-01",
            "content-type: application/json"
        ]);
        
        $decoded = json_decode($response, true);
        return $decoded['content'][0]['text'] ?? "[HANDOFF_REQUIRED]";
    }

    private function makeHttpRequest(string $url, array $data, array $headers): string {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $decoded = json_decode($response, true);
            return $decoded['choices'][0]['message']['content'] ?? $response;
        }

        return "[HANDOFF_REQUIRED]";
    }
}
