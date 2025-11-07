<?php
// auth.php - النسخة المحسّنة
error_reporting(E_ALL);
ini_set('display_errors', 0); // إخفاء الأخطاء في الإنتاج

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة غير مسموحة'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    $pdo = getDB();
    
    // === تسجيل حساب جديد ===
    if ($action === 'register') {
        $name = trim($_POST['name'] ?? '');
        $phone = cleanPhone($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // التحقق من الحقول المطلوبة
        if (empty($name)) {
            throw new Exception('الاسم مطلوب');
        }
        
        if (empty($phone)) {
            throw new Exception('رقم الجوال مطلوب');
        }
        
        if (empty($password)) {
            throw new Exception('كلمة المرور مطلوبة');
        }
        
        if (strlen($password) < 6) {
            throw new Exception('كلمة المرور يجب أن تكون 6 أحرف على الأقل');
        }
        
        // التحقق من صحة رقم الجوال
        if (!isValidSaudiPhone($phone)) {
            throw new Exception('رقم الجوال غير صحيح (يجب أن يبدأ بـ 05 ويتكون من 10 أرقام)');
        }
        
        // التحقق من عدم وجود المستخدم مسبقاً
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        
        if ($stmt->fetch()) {
            throw new Exception('رقم الجوال مسجل مسبقاً');
        }
        
        // تشفير كلمة المرور
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // إدراج المستخدم الجديد
        $stmt = $pdo->prepare("
            INSERT INTO users (name, phone, email, password_hash, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$name, $phone, $email, $passwordHash]);
        
        if ($stmt->rowCount() > 0) {
            $userId = $pdo->lastInsertId();
            
            // تسجيل الدخول تلقائياً
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_phone'] = $phone;
            
            // تسجيل الحدث
            logEvent('info', 'تسجيل مستخدم جديد', [
                'user_id' => $userId,
                'phone' => $phone,
                'name' => $name
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'تم إنشاء الحساب بنجاح وتسجيل الدخول',
                'user' => [
                    'id' => $userId,
                    'name' => $name,
                    'phone' => $phone
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception('فشل إنشاء الحساب');
        }
    }
    
    // === تسجيل الدخول ===
    elseif ($action === 'login') {
        $phone = cleanPhone($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($phone) || empty($password)) {
            throw new Exception('يرجى إدخال رقم الجوال وكلمة المرور');
        }
        
        // البحث عن المستخدم
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('رقم الجوال غير مسجل');
        }
        
        // التحقق من كلمة المرور
        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception('كلمة المرور غير صحيحة');
        }
        
        // تسجيل الدخول
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_phone'] = $user['phone'];
        
        // تسجيل الحدث
        logEvent('info', 'تسجيل دخول مستخدم', [
            'user_id' => $user['id'],
            'phone' => $phone
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'phone' => $user['phone'],
                'email' => $user['email']
            ]
        ], JSON_UNESCAPED_UNICODE);
    }
    
    // === تسجيل الخروج ===
    elseif ($action === 'logout') {
        $userId = $_SESSION['user_id'] ?? null;
        
        session_unset();
        session_destroy();
        
        if ($userId) {
            logEvent('info', 'تسجيل خروج مستخدم', ['user_id' => $userId]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تسجيل الخروج'
        ], JSON_UNESCAPED_UNICODE);
    }
    
    else {
        throw new Exception('إجراء غير معروف');
    }
    
} catch (Exception $e) {
    // تسجيل الخطأ
    error_log("Auth Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}