# AI-First Telegram Live Chat Widget

A lightweight, zero-dependency, and highly customizable PHP library that provides a beautifully designed live chat widget for your website. 

It acts as an **AI-First Support Agent** (powered by OpenAI, Deepseek, or Claude) to handle basic user inquiries. When the AI encounters a question it cannot answer, or if the user explicitly requests to speak to a human, the conversation is seamlessly handed over to your personal Telegram account!

## ✨ Features
*   **🤖 AI-First Routing:** Automatically answer common customer queries using state-of-the-art LLMs.
*   **🤝 Seamless Telegram Handoff:** Instantly transfers chats to a human via a Telegram Bot when required. You can reply directly from your Telegram app.
*   **💅 Premium UI:** Built with Tailwind CSS for a modern, glassmorphic, and animated chat interface.
*   **📊 Admin Dashboard:** Includes a secure, password-protected dashboard to review daily chat transcripts.
*   **🛡️ Secure by Default:** Includes CSRF protection, input sanitization against XSS, rate limiting, and `.htaccess` file protection.
*   **📦 Zero Dependencies:** No Composer required. Just drop it into your PHP project and go.

---

## 📋 Requirements
- PHP 7.4 or higher
- `cURL` extension enabled in PHP
- A Web Server (Apache/Nginx/WAMP/XAMPP)

---

## 🚀 Installation & Setup

### 1. Clone or Copy the Files
Place the contents of this repository into your project directory (e.g., `Telegram_widget`).

### 2. Configure Environment Variables
Rename the `.env.example` file to `.env` and fill in your details:
```ini
TELEGRAM_BOT_TOKEN=your_bot_token_here
ADMIN_CHAT_ID=your_chat_id_here
AI_PROVIDER=openai # Options: openai, claude, deepseek
AI_API_KEY=your_api_key_here
ADMIN_PASSWORD=your_secure_password # Used to log in to the admin dashboard
```

**How to get your Telegram credentials:**
1. Open Telegram and search for `@BotFather`.
2. Send `/newbot`, follow the steps, and copy the **Bot Token** it gives you.
3. Search for your new bot in Telegram and send it a message (e.g., "Hello").
4. To get your **Chat ID**, search for `@userinfobot` on Telegram, start it, and copy your "Id".

### 3. Start Chatting!
Open `index.php` in your browser to see a working demo of the widget.

---

## 💻 How to Embed in an Existing Project

To add the chat widget to any existing PHP page on your site, simply include the `widget.html` snippet at the bottom of your `<body>` tag:

```php
<!-- Your existing website content -->

<!-- Include the Telegram Chat Widget -->
<?php include __DIR__ . '/Telegram_widget/widget.html'; ?>
```
*(Make sure the paths to `widget.js` and `api.php` inside the HTML/JS are correct relative to where you embed it)*.

---

## 🧠 Customizing the AI Persona
You can change how the AI behaves by editing the `$systemPrompt` inside `src/AiAgent.php`. 
**Important:** Do not remove the instruction that tells the AI to output `[HANDOFF_REQUIRED]` when it needs to transfer the chat to a human!

---

## 🔐 Admin Dashboard
To view daily logs and read conversations (both AI-handled and Human-handled):
1. Navigate to `admin.php` in your browser.
2. Log in using the `ADMIN_PASSWORD` you set in your `.env` file.
3. Click on any session ID to view the full transcript.

---

## 🛠️ Folder Structure
- `/src/` - Core PHP classes (`AiAgent`, `ChatSession`, `Config`, `TelegramWidget`).
- `/logs/` - Secure directory where daily JSON chat transcripts are stored.
- `api.php` - The API endpoint the frontend communicates with.
- `admin.php` - The Admin dashboard viewer.
- `widget.html` & `widget.js` - The frontend UI and logic.
- `index.php` - A demo page.
