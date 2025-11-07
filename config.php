<?php
// config.php - النسخة المحسّنة والمصلحة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'homeser_db');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * دالة الحصول على اتصال قاعدة البيانات
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]);
            
            // التأكد من وجود الأدمن الافتراضي
            ensureDefaultAdmin($pdo);
            
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die(json_encode([
                'success' => false,
                'message' => 'خطأ في الاتصال بقاعدة البيانات'
            ], JSON_UNESCAPED_UNICODE));
        }
    }
    
    return $pdo;
}

/**
 * إنشاء حساب أدمن افتراضي
 */
function ensureDefaultAdmin($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = 'admin'");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $username = 'admin';
            $password = 'admin123';
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
            $stmt->execute([$username, $hash]);
        }
    } catch (Exception $e) {
        error_log("Error creating default admin: " . $e->getMessage());
    }
}

/**
 * التحقق من تسجيل دخول الأدمن
 */
function isAdminLogged(): bool {
    return isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
}

/**
 * التحقق من تسجيل دخول المستخدم
 */
function isUserLogged(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * الحصول على بيانات المستخدم الحالي
 */
function getCurrentUser() {
    if (!isUserLogged()) {
        return null;
    }
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, name, phone, email, created_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        return $user ?: null;
    } catch (Exception $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return null;
    }
}

/**
 * تنظيف رقم الجوال
 */
function cleanPhone($phone) {
    return preg_replace('/[^0-9]/', '', trim($phone));
}

/**
 * التحقق من صحة رقم الجوال السعودي
 */
function isValidSaudiPhone($phone) {
    $cleaned = cleanPhone($phone);
    return preg_match('/^(05|5)[0-9]{8}$/', $cleaned);
}

/**
 * تسجيل الأحداث في قاعدة البيانات
 */
function logEvent($level, $message, $meta = []) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO logs (level, message, meta, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([
            $level,
            $message,
            json_encode($meta, JSON_UNESCAPED_UNICODE)
        ]);
    } catch (Exception $e) {
        error_log("Error logging event: " . $e->getMessage());
    }
}