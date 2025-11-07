<?php
require_once dirname(__DIR__) . '/config.php';

// إذا الأدمن مسجل دخوله، انتقل للوحة التحكم
if (isAdminLogged()) {
    header('Location: dashboard.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message = 'يرجى ملء جميع الحقول.';
    } else {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_logged'] = true;
                $_SESSION['admin_username'] = $admin['username'];
                
                // تحديث آخر تسجيل دخول
                $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$admin['id']]);
                
                header('Location: dashboard.php');
                exit;
            } else {
                $message = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
            }
        } catch (Exception $e) {
            error_log("Admin Login Error: " . $e->getMessage());
            $message = 'حدث خطأ أثناء التحقق من البيانات.';
        }
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>تسجيل دخول الأدمن - HomeSer</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { 
    background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 50%,#0f172a 100%); 
    color:#fff; 
    font-family:'Segoe UI',Arial,sans-serif; 
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
}
.container { max-width:480px; width:100%; padding:20px; }
.card { 
    background:rgba(30,41,59,0.8); 
    backdrop-filter:blur(20px);
    padding:40px; 
    border-radius:20px; 
    box-shadow:0 20px 60px rgba(0,0,0,0.5);
    border:1px solid rgba(255,255,255,0.1);
}
.logo {
    text-align:center;
    margin-bottom:30px;
}
.logo-icon {
    font-size:48px;
    display:block;
    margin-bottom:10px;
}
.logo-text {
    font-size:28px;
    font-weight:700;
    background:linear-gradient(135deg,#a855f7,#ec4899);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}
h2 { 
    text-align:center; 
    margin-bottom:10px;
    font-size:24px;
}
.subtitle {
    text-align:center;
    color:#94a3b8;
    font-size:14px;
    margin-bottom:30px;
}
.toast { 
    padding:14px 18px; 
    border-radius:12px; 
    margin-bottom:20px;
    background:linear-gradient(135deg,#fee2e2,#fecaca);
    color:#991b1b;
    border:2px solid #ef4444;
    font-size:14px;
    animation:slideIn 0.3s;
}
@keyframes slideIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
.form-group { margin-bottom:20px; }
label { 
    font-size:14px; 
    color:#cbd5e1; 
    display:block;
    margin-bottom:8px;
    font-weight:500;
}
.input-wrapper {
    position:relative;
}
.input-icon {
    position:absolute;
    right:14px;
    top:50%;
    transform:translateY(-50%);
    font-size:18px;
    color:#64748b;
}
input { 
    width:100%; 
    padding:14px 45px 14px 14px; 
    border-radius:12px; 
    border:1px solid rgba(255,255,255,0.1); 
    background:rgba(15,23,42,0.5); 
    color:#fff;
    font-size:15px;
    transition:all 0.3s;
}
input:focus { 
    outline:none; 
    border-color:#a855f7; 
    background:rgba(15,23,42,0.8);
    box-shadow:0 0 0 4px rgba(168,85,247,0.1);
}
input::placeholder { color:#64748b; }
button { 
    width:100%; 
    padding:14px; 
    border-radius:12px; 
    border:none; 
    background:linear-gradient(135deg,#a855f7,#ec4899); 
    color:#fff; 
    font-weight:600;
    font-size:16px;
    cursor:pointer;
    transition:all 0.3s;
    box-shadow:0 10px 30px rgba(168,85,247,0.4);
}
button:hover { 
    transform:translateY(-2px);
    box-shadow:0 15px 40px rgba(168,85,247,0.6);
}
button:active {
    transform:translateY(0);
}
.footer-note {
    text-align:center;
    margin-top:20px;
    color:#64748b;
    font-size:12px;
}
.footer-note a {
    color:#ec4899;
    text-decoration:none;
}
@media (max-width:480px) {
    .card { padding:30px 20px; }
}
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="logo">
            <span class="logo-icon">🔧</span>
            <div class="logo-text">HomeSer</div>
        </div>
        
        <h2>لوحة تحكم الأدمن</h2>
        <p class="subtitle">سجل دخولك للوصول إلى لوحة التحكم</p>

        <?php if (!empty($message)): ?>
            <div class="toast">⚠️ <?=htmlspecialchars($message)?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="form-group">
                <label>اسم المستخدم</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input type="text" name="username" placeholder="أدخل اسم المستخدم" required autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label>كلمة المرور</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" name="password" placeholder="أدخل كلمة المرور" required>
                </div>
            </div>
            
            <button type="submit">🚀 دخول</button>
        </form>
        
   
</div>
</body>
</html>