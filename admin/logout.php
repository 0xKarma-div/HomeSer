<?php
require_once dirname(__DIR__) . '/config.php';

// تسجيل الحدث قبل الخروج
if (isAdminLogged()) {
    $adminUsername = $_SESSION['admin_username'] ?? 'Unknown';
    
    try {
        logEvent('info', 'تسجيل خروج أدمن', [
            'admin' => $adminUsername,
            'time' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// مسح الجلسة
session_unset();
session_destroy();

// إعادة التوجيه لصفحة تسجيل الدخول
header('Location: login.php');
exit;