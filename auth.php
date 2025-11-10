<?php
require_once 'config.php';

header('Content-Type: application/json');

// التحقق من نوع الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة غير صحيحة']);
    exit;
}

$action = $_POST['action'] ?? '';
$db = getDB();

try {
    
    // === تسجيل مستخدم جديد ===
    if ($action === 'register') {
        
        $name = trim($_POST['name'] ?? '');
        $phone = cleanPhone($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // التحقق من الحقول المطلوبة
        if (empty($name) || empty($phone) || empty($password)) {
            throw new Exception('الرجاء ملء جميع الحقول المطلوبة');
        }
        
        if (strlen($password) < 6) {
            throw new Exception('كلمة المرور يجب أن تكون 6 أحرف على الأقل');
        }
        
        // التحقق من رقم الجوال (يبدأ بـ 05 ويكون 10 أرقام)
        if (!preg_match('/^05[0-9]{8}$/', $phone)) {
            throw new Exception('رقم الجوال غير صحيح');
        }
        
        // التحقق من عدم تكرار رقم الجوال
        $stmt = $db->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            throw new Exception('رقم الجوال مسجل مسبقاً');
        }
        
        // تشفير كلمة المرور
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // إدخال المستخدم في قاعدة البيانات
        $stmt = $db->prepare("INSERT INTO users (name, phone, email, password_hash, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $phone, $email, $hashedPassword]);
        
        // حفظ معلومات المستخدم في الجلسة
        $_SESSION['user_id'] = $db->lastInsertId();
        
        logEvent('info', 'تسجيل مستخدم جديد', ['phone' => $phone]);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إنشاء الحساب بنجاح',
            'user' => ['id' => $_SESSION['user_id'], 'name' => $name, 'phone' => $phone, 'email' => $email]
        ]);
    }
    
    // === تسجيل الدخول ===
    elseif ($action === 'login') {
        
        $phone = cleanPhone($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($phone) || empty($password)) {
            throw new Exception('الرجاء إدخال رقم الجوال وكلمة المرور');
        }
        
        // البحث عن المستخدم في قاعدة البيانات
        $stmt = $db->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception('رقم الجوال غير مسجل');
        }
        
        // التحقق من كلمة المرور
        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception('كلمة المرور غير صحيحة');
        }
        
        // حفظ معلومات المستخدم في الجلسة
        $_SESSION['user_id'] = $user['id'];
        
        logEvent('info', 'تسجيل دخول', ['user_id' => $user['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'user' => ['id' => $user['id'], 'name' => $user['name'], 'phone' => $user['phone'], 'email' => $user['email']]
        ]);
    }
    
    // === تسجيل الخروج ===
    elseif ($action === 'logout') {
        
        session_destroy();
        
        echo json_encode(['success' => true, 'message' => 'تم تسجيل الخروج']);
    }
    
    else {
        throw new Exception('عملية غير معروفة');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>