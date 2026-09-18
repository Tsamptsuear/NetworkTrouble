<?php
/**
 * NetworkTrouble - Application Configuration & Bootstrap
 */

if (session_status() === PHP_SESSION_NONE) {
    // 30 days session cookie lifetime
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// App Constants
define('APP_NAME', 'NetworkTrouble');
define('APP_TAGLINE', 'Understand Your Network. Solve Problems Smarter.');
define('APP_VERSION', '1.0.0');

// Base URL detection helper
function base_url(string $path = ''): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Detect script subfolder automatically (works in XAMPP htdocs/networktrouble or root)
    if (php_sapi_name() === 'cli') {
        $dirName = '/networktrouble';
    } else {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $dirName = str_replace('\\', '/', dirname($scriptName));
        // Strip admin or api subfolder if we're inside one
        $dirName = preg_replace('/(\/admin|\/api|\/config|\/includes).*$/', '', $dirName);
        if ($dirName === '/' || $dirName === '\\' || preg_match('/^[a-zA-Z]:/', $dirName)) {
            $dirName = '';
        }
    }
    
    $cleanPath = ltrim($path, '/');
    return $protocol . $host . $dirName . ($cleanPath ? '/' . $cleanPath : '');
}

/**
 * CSRF Protection
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function generate_csrf_token(): string {
    return csrf_token();
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function verify_csrf_token(?string $token): bool {
    return verify_csrf($token);
}

/**
 * Flash Messaging
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Clean & Sanitize String for database storage (no HTML encoding, pure normal text)
 */
function sanitize(?string $data): string {
    if ($data === null) {
        return '';
    }
    // Remove null bytes and trim whitespace, preserving clean original characters (&, ', ", <, >)
    return str_replace("\0", '', trim($data));
}

/**
 * Global Safe HTML Escaper (guarantees NO double encoding)
 * Encodes special characters only once when rendering to HTML.
 * If string already contains &amp;, &quot;, &lt;, &gt;, it will NOT double-encode.
 */
function e(?string $text): string {
    if ($text === null || $text === '') {
        return '';
    }
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
}
