<?php
// track_api.php - النسخة المحسّنة
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// التحقق من تسجيل الدخول
if (!isUserLogged()) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'يجب تسجيل الدخول أولاً'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $user = getCurrentUser();
    
    if (!$user) {
        throw new Exception('خطأ في جلب بيانات المستخدم');
    }
    
    $pdo = getDB();
    
    // جلب جميع طلبات المستخدم مع الترتيب حسب الأحدث
    $stmt = $pdo->prepare("
        SELECT 
            id,
            address,
            service_type,
            preferred_date,
            description,
            status,
            DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as created_at
        FROM requests 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([$user['id']]);
    $requests = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $requests,
        'count' => count($requests)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Track API Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في تحميل الطلبات'
    ], JSON_UNESCAPED_UNICODE);
}