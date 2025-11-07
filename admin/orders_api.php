<?php
// admin/orders_api.php 
require_once dirname(__DIR__) . '/config.php';

// التحقق من تسجيل دخول الأدمن
if (!isAdminLogged()) {
    $_SESSION['error_message'] = 'يجب تسجيل الدخول أولاً';
    header('Location: login.php');
    exit;
}

// التحقق من أن الطلب POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'طريقة طلب غير صالحة';
    header('Location: dashboard.php');
    exit;
}

$action = $_POST['action'] ?? '';
$requestId = (int)($_POST['id'] ?? 0);

if (empty($action) || $requestId <= 0) {
    $_SESSION['error_message'] = 'بيانات غير صحيحة';
    header('Location: dashboard.php');
    exit;
}

try {
    $pdo = getDB();
    
    switch ($action) {
        case 'set_processing':
            // تحديث حالة الطلب إلى "قيد المعالجة"
            $stmt = $pdo->prepare("UPDATE requests SET status = 'processing' WHERE id = ?");
            $stmt->execute([$requestId]);
            
            if ($stmt->rowCount() > 0) {
                logEvent('info', 'تحديث حالة طلب إلى قيد المعالجة', [
                    'request_id' => $requestId,
                    'admin' => $_SESSION['admin_username'] ?? 'admin'
                ]);
                $_SESSION['success_message'] = 'تم تحديث حالة الطلب إلى "قيد المعالجة" بنجاح';
            } else {
                $_SESSION['error_message'] = 'لم يتم العثور على الطلب أو الحالة نفسها بالفعل';
            }
            break;
            
        case 'set_completed':
            // تحديث حالة الطلب إلى "مكتمل"
            $stmt = $pdo->prepare("UPDATE requests SET status = 'completed' WHERE id = ?");
            $stmt->execute([$requestId]);
            
            if ($stmt->rowCount() > 0) {
                logEvent('info', 'تحديث حالة طلب إلى مكتمل', [
                    'request_id' => $requestId,
                    'admin' => $_SESSION['admin_username'] ?? 'admin'
                ]);
                $_SESSION['success_message'] = 'تم إكمال الطلب بنجاح ✅';
            } else {
                $_SESSION['error_message'] = 'لم يتم العثور على الطلب أو الحالة نفسها بالفعل';
            }
            break;
            
        case 'delete':
            // حذف الطلب نهائياً
            $stmt = $pdo->prepare("DELETE FROM requests WHERE id = ?");
            $stmt->execute([$requestId]);
            
            if ($stmt->rowCount() > 0) {
                logEvent('warning', 'حذف طلب', [
                    'request_id' => $requestId,
                    'admin' => $_SESSION['admin_username'] ?? 'admin'
                ]);
                $_SESSION['success_message'] = 'تم حذف الطلب بنجاح 🗑️';
            } else {
                $_SESSION['error_message'] = 'لم يتم العثور على الطلب المطلوب حذفه';
            }
            break;
            
        default:
            $_SESSION['error_message'] = 'إجراء غير معروف';
            break;
    }
    
} catch (PDOException $e) {
    error_log("Orders API Error: " . $e->getMessage());
    $_SESSION['error_message'] = 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage();
} catch (Exception $e) {
    error_log("Orders API Error: " . $e->getMessage());
    $_SESSION['error_message'] = 'حدث خطأ غير متوقع';
}

// العودة إلى لوحة التحكم
header('Location: dashboard.php');
exit;