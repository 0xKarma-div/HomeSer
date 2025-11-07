<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/config.php';

// التحقق من تسجيل دخول الأدمن
if (!isAdminLogged()) {
    header('Location: login.php');
    exit;
}

$pdo = getDB();
$requests = [];
$stats = [
    'total' => 0,
    'pending' => 0,
    'processing' => 0,
    'completed' => 0,
];
$error = null;

try {
    // جلب الإحصائيات
    $stats['total'] = (int)$pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();
    $stats['pending'] = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE status='pending'")->fetchColumn();
    $stats['processing'] = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE status='processing'")->fetchColumn();
    $stats['completed'] = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE status='completed'")->fetchColumn();
    
    // جلب جميع الطلبات مع بيانات العملاء
    $stmt = $pdo->query("
        SELECT 
            r.id,
            r.address,
            r.service_type,
            r.preferred_date,
            r.description,
            r.status,
            DATE_FORMAT(r.created_at, '%Y-%m-%d %H:%i') as created_at,
            u.name as user_name,
            u.phone,
            u.email
        FROM requests r 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC
    ");
    
    $requests = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Dashboard Error: " . $error);
}

// رسائل النجاح/الخطأ
$successMsg = $_SESSION['success_message'] ?? '';
$errorMsg = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>لوحة تحكم الأدمن - HomeSer</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',Arial,sans-serif; background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 50%,#0f172a 100%); color:#fff; min-height:100vh; }
.header { background:rgba(30,41,59,0.95); backdrop-filter:blur(20px); padding:20px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 4px 20px rgba(0,0,0,0.4); border-bottom:1px solid rgba(255,255,255,0.1); }
.header h1 { font-size:26px; background:linear-gradient(135deg,#a855f7,#ec4899); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
.header-right { display:flex; align-items:center; gap:20px; }
.header-right span { color:#cbd5e1; font-size:14px; }
.header a { color:#ec4899; text-decoration:none; font-weight:600; transition:0.3s; padding:10px 20px; border-radius:8px; background:rgba(236,72,153,0.1); }
.header a:hover { background:rgba(236,72,153,0.2); transform:translateY(-2px); }
.container { max-width:1400px; margin:30px auto; padding:0 20px; }
.stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin-bottom:40px; }
.stat-card { background:linear-gradient(135deg,rgba(168,85,247,0.1),rgba(236,72,153,0.1)); backdrop-filter:blur(20px); padding:30px; border-radius:16px; border:1px solid rgba(255,255,255,0.1); transition:0.3s; }
.stat-card:hover { transform:translateY(-5px); box-shadow:0 10px 30px rgba(0,0,0,0.3); }
.stat-card h3 { font-size:14px; color:#cbd5e1; margin-bottom:12px; font-weight:500; }
.stat-card .number { font-size:42px; font-weight:700; background:linear-gradient(135deg,#a855f7,#ec4899); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
.toast { padding:16px 24px; border-radius:12px; margin-bottom:24px; animation:slideIn 0.4s; display:flex; align-items:center; gap:12px; font-weight:500; }
.toast.success { background:linear-gradient(135deg,#d1fae5,#a7f3d0); color:#065f46; border:2px solid #10b981; }
.toast.error { background:linear-gradient(135deg,#fee2e2,#fecaca); color:#991b1b; border:2px solid #ef4444; }
@keyframes slideIn { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }
.table-container { background:rgba(30,41,59,0.5); backdrop-filter:blur(20px); border-radius:16px; overflow:hidden; border:1px solid rgba(255,255,255,0.1); box-shadow:0 10px 40px rgba(0,0,0,0.3); }
table { width:100%; border-collapse:collapse; }
th, td { padding:18px 16px; text-align:right; border-bottom:1px solid rgba(255,255,255,0.05); }
th { background:rgba(15,23,42,0.8); font-weight:600; font-size:14px; color:#cbd5e1; }
tr { transition:0.2s; }
tr:hover { background:rgba(255,255,255,0.05); }
.badge { padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:6px; }
.badge.pending { background:rgba(245,158,11,0.2); color:#fbbf24; border:1px solid rgba(251,191,36,0.3); }
.badge.processing { background:rgba(59,130,246,0.2); color:#60a5fa; border:1px solid rgba(96,165,250,0.3); }
.badge.completed { background:rgba(16,185,129,0.2); color:#34d399; border:1px solid rgba(52,211,153,0.3); }
.actions { display:flex; gap:8px; flex-wrap:wrap; }
.btn { padding:8px 16px; border:none; border-radius:8px; cursor:pointer; font-size:13px; font-weight:600; transition:0.3s; color:#fff; }
.btn:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.4); }
.btn-primary { background:linear-gradient(135deg,#3b82f6,#2563eb); }
.btn-success { background:linear-gradient(135deg,#10b981,#059669); }
.btn-danger { background:linear-gradient(135deg,#ef4444,#dc2626); }
.empty-state { text-align:center; padding:80px 20px; }
.empty-state .icon { font-size:72px; margin-bottom:20px; opacity:0.4; }
.empty-state h3 { font-size:24px; margin-bottom:12px; color:#64748b; }
.empty-state p { color:#64748b; font-size:15px; }
@media (max-width:768px) {
    .header { flex-direction:column; gap:15px; text-align:center; }
    .header-right { flex-direction:column; gap:10px; }
    .stats { grid-template-columns:1fr; }
    table { font-size:13px; }
    th, td { padding:12px 8px; }
    .actions { flex-direction:column; }
    .btn { width:100%; }
}
</style>
</head>
<body>

<div class="header">
    <h1>🏠 لوحة تحكم HomeSer</h1>
    <div class="header-right">
        <span>مرحباً، <?=htmlspecialchars($_SESSION['admin_username'] ?? 'Admin')?> 👋</span>
        <a href="logout.php">تسجيل الخروج ➜</a>
    </div>
</div>

<div class="container">
    <?php if ($error): ?>
        <div class="toast error">
            <span>❌</span>
            <span><strong>خطأ:</strong> <?=htmlspecialchars($error)?></span>
        </div>
    <?php endif; ?>

    <?php if ($successMsg): ?>
        <div class="toast success">
            <span>✓</span>
            <span><?=htmlspecialchars($successMsg)?></span>
        </div>
    <?php endif; ?>
    
    <?php if ($errorMsg): ?>
        <div class="toast error">
            <span>❌</span>
            <span><?=htmlspecialchars($errorMsg)?></span>
        </div>
    <?php endif; ?>

    <!-- البطاقات الإحصائية -->
    <div class="stats">
        <div class="stat-card">
            <h3>📊 إجمالي الطلبات</h3>
            <div class="number"><?=$stats['total']?></div>
        </div>
        <div class="stat-card">
            <h3>⏳ قيد الانتظار</h3>
            <div class="number"><?=$stats['pending']?></div>
        </div>
        <div class="stat-card">
            <h3>⚙️ قيد المعالجة</h3>
            <div class="number"><?=$stats['processing']?></div>
        </div>
        <div class="stat-card">
            <h3>✅ مكتملة</h3>
            <div class="number"><?=$stats['completed']?></div>
        </div>
    </div>

    <!-- جدول الطلبات -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>رقم الطلب</th>
                    <th>العميل</th>
                    <th>الجوال</th>
                    <th>الخدمة</th>
                    <th>التاريخ المفضل</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="icon">📋</div>
                                <h3>لا توجد طلبات حالياً</h3>
                                <p>سيتم عرض الطلبات الجديدة هنا تلقائياً</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                    <tr>
                        <td><strong>#<?=$req['id']?></strong></td>
                        <td><?=htmlspecialchars($req['user_name'])?></td>
                        <td><?=htmlspecialchars($req['phone'])?></td>
                        <td><?=htmlspecialchars($req['service_type'])?></td>
                        <td><?=date('Y/m/d', strtotime($req['preferred_date']))?></td>
                        <td>
                            <?php if ($req['status'] === 'pending'): ?>
                                <span class="badge pending">⏳ قيد الانتظار</span>
                            <?php elseif ($req['status'] === 'processing'): ?>
                                <span class="badge processing">⚙️ قيد المعالجة</span>
                            <?php else: ?>
                                <span class="badge completed">✅ مكتمل</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <?php if ($req['status'] !== 'processing'): ?>
                                <form method="post" action="orders_api.php" style="display:inline">
                                    <input type="hidden" name="id" value="<?=$req['id']?>">
                                    <input type="hidden" name="action" value="set_processing">
                                    <button type="submit" class="btn btn-primary">⚙️ معالجة</button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if ($req['status'] !== 'completed'): ?>
                                <form method="post" action="orders_api.php" style="display:inline">
                                    <input type="hidden" name="id" value="<?=$req['id']?>">
                                    <input type="hidden" name="action" value="set_completed">
                                    <button type="submit" class="btn btn-success">✅ إكمال</button>
                                </form>
                                <?php endif; ?>
                                
                                <form method="post" action="orders_api.php" style="display:inline" 
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذا الطلب؟')">
                                    <input type="hidden" name="id" value="<?=$req['id']?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-danger">🗑️ حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>