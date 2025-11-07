<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (isUserLogged()) {
        $user = getCurrentUser();
        
        if ($user) {
            echo json_encode([
                'logged_in' => true,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'phone' => $user['phone'],
                    'email' => $user['email']
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            // المستخدم مسجل في الجلسة لكن لا يوجد في قاعدة البيانات
            session_unset();
            session_destroy();
            
            echo json_encode(['logged_in' => false], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(['logged_in' => false], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    error_log("Check Auth Error: " . $e->getMessage());
    
    echo json_encode(['logged_in' => false], JSON_UNESCAPED_UNICODE);
}