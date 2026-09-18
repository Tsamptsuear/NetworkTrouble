<?php
/**
 * NetworkTrouble - Database Configuration
 * PDO Connection with graceful error handling & auto-reconnect
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'networktrouble_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function get_db(): ?PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci",
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // If the database does not exist, attempt to check if MySQL server is reachable
        try {
            $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
            $checkPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
            // Server reachable, but database not created or imported
            return null;
        } catch (PDOException $ex) {
            // MySQL server itself is not running
            return null;
        }
    }
}

/**
 * Global database connection helper
 * Alias for get_db() used by admin and API modules
 */
function get_db_connection(): ?PDO {
    return get_db();
}

/**
 * Helper to check if database is installed and populated
 */
function is_database_ready(): bool {
    $db = get_db();
    if (!$db) {
        return false;
    }
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'network_categories'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}
