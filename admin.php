<?php
session_start();
require_once __DIR__ . '/src/Config.php';
use TelegramWidget\Config;

$config = new Config(__DIR__ . '/.env');
$adminPassword = $config->get('ADMIN_PASSWORD', 'admin');

// Handle Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header("Location: admin.php");
    exit;
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $adminPassword) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "Invalid password.";
    }
}

$isLoggedIn = $_SESSION['admin_logged_in'] ?? false;

// Helper to get logs
function getDailyLogs() {
    $baseDir = __DIR__ . '/logs';
    if (!is_dir($baseDir)) return [];
    
    $logs = [];
    $dates = array_diff(scandir($baseDir, SCANDIR_SORT_DESCENDING), ['..', '.']);
    
    foreach ($dates as $date) {
        $dateDir = $baseDir . '/' . $date;
        if (is_dir($dateDir)) {
            $files = glob($dateDir . '/*.json');
            foreach ($files as $file) {
                $logs[$date][] = $file;
            }
        }
    }
    return $logs;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Widget Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    
    <?php if (!$isLoggedIn): ?>
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white p-8 rounded-xl shadow-lg w-96">
                <h1 class="text-2xl font-bold mb-6 text-gray-800 text-center">Admin Login</h1>
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST" class="flex flex-col gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">Login</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <nav class="bg-white shadow-sm px-6 py-4 flex justify-between items-center mb-8">
            <h1 class="text-xl font-bold text-gray-800">Widget Dashboard</h1>
            <a href="?logout=1" class="text-sm text-red-600 hover:underline">Logout</a>
        </nav>

        <div class="max-w-6xl mx-auto px-6 pb-12">
            
            <?php
            // View specific log
            if (isset($_GET['view'])) {
                $path = realpath($_GET['view']);
                // Security check to ensure it's inside logs dir
                if ($path && strpos($path, realpath(__DIR__ . '/logs')) === 0) {
                    $data = json_decode(file_get_contents($path), true);
                    echo "<div class='mb-4'><a href='admin.php' class='text-blue-600 hover:underline'>&larr; Back to Dashboard</a></div>";
                    echo "<div class='bg-white rounded-xl shadow p-6'>";
                    echo "<h2 class='text-lg font-bold mb-4 border-b pb-2'>Session: " . htmlspecialchars(basename($path, '.json')) . "</h2>";
                    
                    echo "<div class='mb-6'>";
                    echo "<span class='px-2 py-1 text-xs font-semibold rounded " . ($data['state']['human_mode'] ? "bg-green-100 text-green-800" : "bg-blue-100 text-blue-800") . "'>";
                    echo $data['state']['human_mode'] ? 'Handled by Human' : 'Handled by AI';
                    echo "</span></div>";

                    echo "<div class='flex flex-col gap-4 bg-gray-50 p-4 rounded-lg'>";
                    if (empty($data['messages'])) {
                        echo "<p class='text-gray-500'>No messages.</p>";
                    } else {
                        foreach ($data['messages'] as $msg) {
                            $role = htmlspecialchars($msg['role']);
                            $content = nl2br(htmlspecialchars($msg['content']));
                            $time = date('H:i:s', $msg['timestamp']);
                            
                            $color = 'bg-white';
                            if ($role === 'user') $color = 'bg-blue-50';
                            if ($role === 'ai') $color = 'bg-purple-50';
                            if ($role === 'human') $color = 'bg-green-50';
                            if ($role === 'system') $color = 'bg-yellow-50 text-sm';

                            echo "<div class='{$color} p-3 rounded shadow-sm border border-gray-100'>";
                            echo "<div class='text-xs text-gray-400 mb-1 uppercase font-semibold'>{$role} <span class='float-right font-normal lowercase'>{$time}</span></div>";
                            echo "<div class='text-gray-800'>{$content}</div>";
                            echo "</div>";
                        }
                    }
                    echo "</div></div>";
                } else {
                    echo "<div class='text-red-500'>Invalid log file.</div>";
                }
            } else {
                // List logs
                $logs = getDailyLogs();
                if (empty($logs)) {
                    echo "<div class='bg-white p-6 rounded-xl shadow text-center text-gray-500'>No conversation logs found.</div>";
                } else {
                    foreach ($logs as $date => $files) {
                        echo "<h2 class='text-xl font-bold text-gray-700 mb-4 mt-8'>{$date}</h2>";
                        echo "<div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4'>";
                        foreach ($files as $file) {
                            $data = json_decode(file_get_contents($file), true);
                            $msgCount = count($data['messages'] ?? []);
                            $isHuman = $data['state']['human_mode'] ?? false;
                            $sessId = basename($file, '.json');
                            
                            echo "<a href='?view=" . urlencode($file) . "' class='block bg-white p-5 rounded-xl shadow hover:shadow-md transition border border-transparent hover:border-blue-200'>";
                            echo "<div class='flex justify-between items-start mb-2'>";
                            echo "<h3 class='font-mono text-sm text-gray-800 truncate' title='{$sessId}'>{$sessId}</h3>";
                            echo "</div>";
                            echo "<div class='flex items-center gap-2 text-sm'>";
                            echo "<span class='px-2 py-0.5 rounded text-xs font-semibold " . ($isHuman ? "bg-green-100 text-green-700" : "bg-blue-100 text-blue-700") . "'>" . ($isHuman ? 'Human' : 'AI') . "</span>";
                            echo "<span class='text-gray-500 text-xs'>{$msgCount} messages</span>";
                            echo "</div>";
                            echo "</a>";
                        }
                        echo "</div>";
                    }
                }
            }
            ?>
        </div>
    <?php endif; ?>

</body>
</html>
