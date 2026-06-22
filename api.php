<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/ChatSession.php';
require_once __DIR__ . '/src/AiAgent.php';
require_once __DIR__ . '/src/TelegramWidget.php';

use TelegramWidget\Config;
use TelegramWidget\ChatSession;
use TelegramWidget\AiAgent;
use TelegramWidget\TelegramWidget;

$config = new Config(__DIR__ . '/.env');
$action = $_GET['action'] ?? '';

// Rate Limiting (10 requests per 10 seconds for simplicity)
if (!isset($_SESSION['rate_limit'])) {
    $_SESSION['rate_limit'] = ['count' => 0, 'time' => time()];
}
if (time() - $_SESSION['rate_limit']['time'] > 10) {
    $_SESSION['rate_limit'] = ['count' => 0, 'time' => time()];
}
$_SESSION['rate_limit']['count']++;
if ($_SESSION['rate_limit']['count'] > 20) {
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}

// CSRF Generation
if ($action === 'init') {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    $sessionId = $_GET['session_id'] ?? '';
    $chatSession = new ChatSession($sessionId);
    
    echo json_encode([
        'csrf_token' => $_SESSION['csrf_token'],
        'session_id' => $chatSession->getSessionId(),
        'messages' => $chatSession->getMessages(),
        'human_mode' => $chatSession->isHumanMode()
    ]);
    exit;
}

// CSRF Verification for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $headers = getallheaders();
    $token = $headers['X-CSRF-Token'] ?? '';
    if (empty($token) || $token !== $_SESSION['csrf_token']) {
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}

$sessionId = $_POST['session_id'] ?? $_GET['session_id'] ?? '';
if (empty($sessionId)) {
    echo json_encode(['error' => 'Missing session ID']);
    exit;
}

$chatSession = new ChatSession($sessionId);

if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    if (empty($message)) {
        echo json_encode(['error' => 'Empty message']);
        exit;
    }

    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); // Sanitize
    $chatSession->addMessage('user', $message);

    if ($chatSession->isHumanMode()) {
        // Forward to Telegram directly
        $telegram = new TelegramWidget($config);
        $telegram->sendMessage("[{$chatSession->getSessionId()}]\nUser: {$message}");
        echo json_encode(['status' => 'sent', 'human_mode' => true]);
        exit;
    } else {
        // Send to AI
        $ai = new AiAgent($config);
        $aiResponse = $ai->generateReply($chatSession);

        if (strpos($aiResponse, '[HANDOFF_REQUIRED]') !== false) {
            $chatSession->setHumanMode(true);
            $sysMsg = "I am transferring you to a human agent. Please hold on...";
            $chatSession->addMessage('ai', $sysMsg);
            
            // Forward entire history to Telegram
            $telegram = new TelegramWidget($config);
            $historyStr = "New Handoff! Session: [{$chatSession->getSessionId()}]\n\nTranscript:\n";
            foreach ($chatSession->getMessages() as $msg) {
                $historyStr .= strtoupper($msg['role']) . ": " . $msg['content'] . "\n";
            }
            $telegram->sendMessage($historyStr);
            
            echo json_encode(['reply' => $sysMsg, 'human_mode' => true]);
        } else {
            $chatSession->addMessage('ai', $aiResponse);
            echo json_encode(['reply' => $aiResponse, 'human_mode' => false]);
        }
        exit;
    }
}

if ($action === 'poll') {
    if ($chatSession->isHumanMode()) {
        // Check Telegram for replies
        $telegram = new TelegramWidget($config);
        
        // We track the last update ID in a file or session.
        // For simplicity, let's keep it in the session or read the last file.
        $lastUpdateId = $_SESSION['last_update_id'] ?? 0;
        $updates = $telegram->getUpdates($lastUpdateId + 1);
        
        $newMessages = [];
        
        foreach ($updates as $update) {
            $_SESSION['last_update_id'] = $update['update_id'];
            
            if (isset($update['message']['text']) && isset($update['message']['reply_to_message'])) {
                $replyText = $update['message']['text'];
                $originalText = $update['message']['reply_to_message']['text'] ?? '';
                
                // If admin replies to a message containing [sess_xxx]
                if (preg_match('/\[(sess_[a-zA-Z0-9_-]+)\]/', $originalText, $matches)) {
                    $targetSessionId = $matches[1];
                    if ($targetSessionId === $chatSession->getSessionId()) {
                        // It's a reply for this user!
                        $chatSession->addMessage('human', htmlspecialchars($replyText, ENT_QUOTES, 'UTF-8'));
                        $newMessages[] = [
                            'role' => 'human',
                            'content' => $replyText
                        ];
                    } else {
                        // It's a reply for another user, save it to their session
                        $otherSession = new ChatSession($targetSessionId);
                        $otherSession->addMessage('human', htmlspecialchars($replyText, ENT_QUOTES, 'UTF-8'));
                    }
                }
            }
        }
        
        echo json_encode(['messages' => $newMessages]);
        exit;
    }
    
    echo json_encode(['messages' => []]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
