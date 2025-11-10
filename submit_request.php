<?php
require_once 'config.php';

header('Content-Type: application/json');

// التحقق من تسجيل الدخول
if (!isUserLogged()) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

try {
    
    $db = getDB();
    
    // جلب طلبات المستخدم من قاعدة البيانات (الأحدث أولاً)
    $stmt = $db->prepare("
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
    
    $stmt->execute([$_SESSION['user_id']]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $requests,
        'count' => count($requests)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في تحميل الطلبات']);
}
?>