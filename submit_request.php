<?php
// submit_request.php - النسخة المحسّنة
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة غير مسموحة'], JSON_UNESCAPED_UNICODE);
    exit;
}

// التحقق من تسجيل الدخول
if (!isUserLogged()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // جمع البيانات
    $address = trim($_POST['address'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // التحقق من البيانات
    if (empty($address)) {
        throw new Exception('العنوان مطلوب');
    }
    
    if (empty($service)) {
        throw new Exception('نوع الخدمة مطلوب');
    }
    
    if (empty($date)) {
        throw new Exception('التاريخ المفضل مطلوب');
    }
    
    if (empty($description)) {
        throw new Exception('وصف المشكلة مطلوب');
    }
    
    // التحقق من صحة التاريخ
    $selectedDate = strtotime($date);
    $today = strtotime('today');
    
    if ($selectedDate < $today) {
        throw new Exception('يرجى اختيار تاريخ في المستقبل');
    }
    
    // التحقق من نوع الخدمة
    $validServices = ['electrical', 'plumbing', 'ac', 'general'];
    if (!in_array($service, $validServices)) {
        throw new Exception('نوع خدمة غير صالح');
    }
    
    // الحصول على معلومات المستخدم
    $user = getCurrentUser();
    if (!$user) {
        throw new Exception('خطأ في جلب بيانات المستخدم');
    }
    
    // إدراج الطلب
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO requests (user_id, address, service_type, preferred_date, description, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $user['id'],
        $address,
        $service,
        $date,
        $description
    ]);
    
    if ($stmt->rowCount() > 0) {
        $requestId = $pdo->lastInsertId();
        
        // تسجيل الحدث
        logEvent('info', 'طلب خدمة جديد', [
            'request_id' => $requestId,
            'user_id' => $user['id'],
            'service_type' => $service,
            'customer_name' => $user['name'],
            'customer_phone' => $user['phone']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إرسال الطلب بنجاح! سنتواصل معك قريباً',
            'request_id' => $requestId
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('فشل إرسال الطلب');
    }
    
} catch (Exception $e) {
    error_log("Submit Request Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}