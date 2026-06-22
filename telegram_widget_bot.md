# Product Requirements Document (PRD) & Implementation Plan
## Project: AI-First Telegram Live Chat Widget

### 1. Overview
A reusable PHP library for a website live chat widget. The widget intercepts user messages and routes them to an AI Agent (OpenAI, Claude, or Deepseek) to handle level-1 support. If the user explicitly requests a human, or if the AI determines it cannot answer the question, the system hands the conversation over to a human operator via a Telegram Bot. The human operator can also review all daily conversations, including those handled entirely by AI.

### 2. Core Features
*   **AI-First Routing:** Messages are first sent to a configurable LLM.
*   **Seamless Handoff:** The AI can trigger a handoff to a human. When triggered, the entire conversation history is forwarded to the Admin's Telegram app.
*   **Telegram Integration:** Once in "Human Mode", messages from the website go to Telegram, and replies from Telegram go back to the website.
*   **Daily Conversation Log:** All sessions are logged locally.
*   **Admin Viewer:** A secure page (`admin.php`) to review the day's conversation transcripts.
*   **Library Structure:** Easily embeddable into any existing PHP project using an `index.php` and an HTML snippet.
*   **Tailwind CSS Frontend:** Modern, premium UI design.

### 3. Architecture & File Structure

#### Backend (PHP Library)
*   **`src/Config.php`**: Parses `.env` securely.
*   **`src/TelegramWidget.php`**: Handles communication with the Telegram Bot API (`sendMessage`, `getUpdates`).
*   **`src/AiAgent.php`**: Handles API calls to the configured LLM (OpenAI/Claude/Deepseek). Uses a system prompt to define the persona and the handoff mechanism.
*   **`src/ChatSession.php`**: Manages the state of the user's session (AI vs Human mode) and logs the conversation history into `logs/YYYY-MM-DD/session_id.json`.
*   **`api.php`**: The secure endpoint that the JavaScript frontend communicates with.
*   **`admin.php`**: A password-protected page to view the `logs/` directory.

#### Frontend
*   **`widget.html`**: The UI template styled with Tailwind CSS.
*   **`widget.js`**: Handles polling, sending messages, and UI updates.

### 4. AI Handoff Mechanism
The AI will be instructed via its System Prompt:
> "You are a helpful customer support agent. If you do not know the answer to a question, or if the user asks to speak to a human, you must output the exact string: `[HANDOFF_REQUIRED]` and nothing else."

The `AiAgent.php` will check the response. If it sees `[HANDOFF_REQUIRED]`:
1. It updates the `ChatSession` state to `human_mode = true`.
2. It sends a summary/transcript of the chat to the Telegram Bot.
3. It returns a system message to the frontend: *"I am transferring you to a human agent. Please hold on..."*

### 5. Security Practices (Best Practices)
*   **File Protection:** The `.env` and `logs/` folders will include `.htaccess` files `Deny from all` to prevent direct browser access.
*   **Input Sanitization:** All user inputs will be escaped using `htmlspecialchars()` before saving or displaying to prevent Cross-Site Scripting (XSS).
*   **Rate Limiting:** `api.php` will implement basic session-based rate limiting (e.g., max 10 messages per minute) to prevent abuse and API cost exhaustion.
*   **Authentication:** `admin.php` will be secured with a hashed password defined in the `.env` file.
*   **CSRF Protection:** The frontend will fetch a CSRF token on load and send it with every message to `api.php`.

### 6. Implementation Steps
1.  **Environment Setup:** Create the `.env` schema (`TELEGRAM_BOT_TOKEN`, `ADMIN_CHAT_ID`, `AI_PROVIDER`, `AI_API_KEY`, `ADMIN_PASSWORD`).
2.  **Core Classes:** Implement `ChatSession` (logging/state) and `AiAgent` (LLM communication).
3.  **Telegram Integration:** Implement `TelegramWidget` for sending/polling.
4.  **API Layer:** Build `api.php` connecting the classes with CSRF and rate limiting.
5.  **Frontend:** Design `widget.html` with Tailwind and write `widget.js`.
6.  **Admin Viewer:** Build the `admin.php` UI to parse and display the JSON logs.
7.  **Testing:** End-to-end testing locally using WAMP.
