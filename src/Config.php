<?php
namespace TelegramWidget;

class Config {
    private array $env = [];

    public function __construct(string $envPath) {
        if (file_exists($envPath)) {
            $this->loadEnv($envPath);
        }
    }

    private function loadEnv(string $path): void {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Remove inline comments
            if (strpos($value, '#') !== false) {
                $value = trim(substr($value, 0, strpos($value, '#')));
            }

            $this->env[$name] = $value;
        }
    }

    public function get(string $key, $default = null) {
        return $this->env[$key] ?? $default;
    }
}
