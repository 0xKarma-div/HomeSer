<?php
require_once 'config.php';

header('Content-Type: application/json');

// التحقق من حالة تسجيل الدخول
if (isUserLogged()) {
    $user = getCurrentUser();
    
    if ($user) {
        echo json_encode([
            'logged_in' => true,
            'user' => $user
        ]);
    } else {
        // المستخدم في الجلسة لكن غير موجود في قاعدة البيانات
        session_destroy();
        echo json_encode(['logged_in' => false]);
    }
} else {
    echo json_encode(['logged_in' => false]);
}
?>