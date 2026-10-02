<?php
/**
 * PHP Built-in Server Router
 * NexusGaming Platform
 */

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normalize URI
$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

// Serve existing static files (CSS, JS, images, fonts)
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    if ($ext !== 'php') {
        $mimeTypes = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'json' => 'application/json',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
        ];

        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }
        readfile($filePath);
        return true;
    }
}

// Route mapping table
$routes = [
    // Installer Routes
    '/install'              => 'install/index.php',
    '/install/index.php'    => 'install/index.php',

    // User Routes
    '/'                     => 'index.php',
    '/index.php'            => 'index.php',
    '/login'                => 'login.php',
    '/login.php'            => 'login.php',
    '/register'             => 'register.php',
    '/register.php'         => 'register.php',
    '/forgot-password'      => 'forgot_password.php',
    '/forgot_password.php'  => 'forgot_password.php',
    '/dashboard'            => 'dashboard.php',
    '/dashboard.php'        => 'dashboard.php',
    '/wallet'               => 'wallet.php',
    '/wallet.php'           => 'wallet.php',
    '/profile'              => 'profile.php',
    '/profile.php'          => 'profile.php',
    '/notifications'        => 'notifications.php',
    '/notifications.php'    => 'notifications.php',
    '/support'              => 'support.php',
    '/support.php'          => 'support.php',
    '/support-ticket'       => 'support_ticket.php',
    '/support_ticket.php'   => 'support_ticket.php',
    '/logout'               => 'logout.php',
    '/logout.php'           => 'logout.php',

    // Admin Routes
    '/admin'                => 'admin/index.php',
    '/admin/index.php'      => 'admin/index.php',
    '/admin/login'          => 'admin/login.php',
    '/admin/login.php'      => 'admin/login.php',
    '/admin/logout'         => 'admin/logout.php',
    '/admin/logout.php'     => 'admin/logout.php',
    '/admin/users'          => 'admin/users.php',
    '/admin/users.php'      => 'admin/users.php',
    '/admin/transactions'   => 'admin/transactions.php',
    '/admin/transactions.php'=> 'admin/transactions.php',
    '/admin/games'          => 'admin/games.php',
    '/admin/games.php'      => 'admin/games.php',
    '/admin/notifications'  => 'admin/notifications.php',
    '/admin/notifications.php'=> 'admin/notifications.php',
    '/admin/tickets'        => 'admin/tickets.php',
    '/admin/tickets.php'    => 'admin/tickets.php',
    '/admin/settings'       => 'admin/settings.php',
    '/admin/settings.php'   => 'admin/settings.php',
    '/admin/update'         => 'admin/update.php',
    '/admin/update.php'     => 'admin/update.php',
    '/admin/profile'        => 'admin/profile.php',
    '/admin/profile.php'    => 'admin/profile.php',
];

if (isset($routes[$uri])) {
    $targetFile = __DIR__ . '/' . $routes[$uri];
    if (file_exists($targetFile)) {
        require $targetFile;
        return true;
    }
}

// 404 Not Found Page
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found | NexusGaming</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#07090e] text-slate-100 min-h-screen flex items-center justify-center p-6">
    <div class="text-center space-y-4 max-w-md">
        <div class="w-16 h-16 rounded-2xl bg-brand-500/10 border border-brand-500/30 flex items-center justify-center text-brand-400 mx-auto text-2xl font-black">
            404
        </div>
        <h1 class="text-2xl font-bold text-white">Route Not Found</h1>
        <p class="text-xs text-slate-400">The requested resource could not be located on the platform server.</p>
        <div class="pt-4">
            <a href="/" class="px-6 py-2.5 rounded-xl font-bold text-xs text-white bg-sky-600 hover:bg-sky-500 transition-colors inline-block">
                Return to Home
            </a>
        </div>
    </div>
</body>
</html>
