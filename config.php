<?php
// config.php - النسخة المبسطة والمصلحة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// إعدادات قاعدة البيانات - عدّل هذه القيم حسب إعداداتك
define('DB_HOST', 'localhost');
define('DB_NAME', 'homeser_db');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * الاتصال بقاعدة البيانات
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            // إنشاء الأدمن الافتراضي
            ensureDefaultAdmin($pdo);
            
        } catch (PDOException $e) {
            // تسجيل الخطأ
            error_log("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
            
            // رسالة خطأ للمستخدم
            die(json_encode([
                'success' => false,
                'message' => 'خطأ في الاتصال بقاعدة البيانات. تأكد من إعدادات قاعدة البيانات.'
            ], JSON_UNESCAPED_UNICODE));
        }
    }
    
    return $pdo;
}

/**
 * إنشاء حساب الأدمن الافتراضي
 */
function ensureDefaultAdmin($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE username = 'admin'");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $hash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES ('admin', ?)");
            $stmt->execute([$hash]);
        }
    } catch (Exception $e) {
        error_log("خطأ في إنشاء الأدمن: " . $e->getMessage());
    }
}

/**
 * التحقق من تسجيل دخول المستخدم
 */
function isUserLogged() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * التحقق من تسجيل دخول الأدمن
 */
function isAdminLogged() {
    return isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
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
        $stmt = $pdo->prepare("SELECT id, name, phone, email FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("خطأ في جلب المستخدم: " . $e->getMessage());
        return null;
    }
}

/**
 * تنظيف رقم الجوال (إزالة كل شيء عدا الأرقام)
 */
function cleanPhone($phone) {
    return preg_replace('/[^0-9]/', '', trim($phone));
}

/**
 * التحقق من صحة رقم الجوال السعودي
 */
function isValidSaudiPhone($phone) {
    $phone = cleanPhone($phone);
    // يجب أن يبدأ بـ 05 أو 5 ويكون 10 أرقام
    return preg_match('/^(05|5)[0-9]{8}$/', $phone);
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
        error_log("خطأ في تسجيل الحدث: " . $e->getMessage());
    }
}