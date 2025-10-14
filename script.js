// مصفوفة لحفظ الطلبات
let requests = [];

// بيانات الخدمات
const services = {
    electrical: { 
        name: 'خدمات كهربائية', 
        icon: '⚡', 
        color: 'linear-gradient(135deg, #fbbf24, #f97316)' 
    },
    plumbing: { 
        name: 'خدمات سباكة', 
        icon: '💧', 
        color: 'linear-gradient(135deg, #60a5fa, #06b6d4)' 
    },
    ac: { 
        name: 'صيانة مكيفات', 
        icon: '🌬️', 
        color: 'linear-gradient(135deg, #34d399, #14b8a6)' 
    },
    general: { 
        name: 'صيانة عامة', 
        icon: '🔧', 
        color: 'linear-gradient(135deg, #a855f7, #ec4899)' 
    }
};

/**
 * دالة لعرض الصفحة المطلوبة
 * @param {string} pageName - اسم الصفحة (home, request, track)
 */
function showPage(pageName) {
    // إخفاء جميع الصفحات
    const pages = document.querySelectorAll('.page');
    pages.forEach(page => {
        page.classList.remove('active');
    });
    
    // عرض الصفحة المطلوبة
    const selectedPage = document.getElementById(pageName);
    selectedPage.classList.add('active');
    
    // تحديث أزرار القائمة
    const navButtons = document.querySelectorAll('.nav-btn');
    navButtons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.page === pageName) {
            btn.classList.add('active');
        }
    });

    // تحديث قائمة الطلبات إذا كانت صفحة التتبع
    if (pageName === 'track') {
        displayRequests();
    }
}

/**
 * دالة لإرسال طلب جديد
 */
function submitRequest() {
    // الحصول على قيم الحقول
    const name = document.getElementById('name').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const email = document.getElementById('email').value.trim();
    const address = document.getElementById('address').value.trim();
    const service = document.getElementById('service').value;
    const date = document.getElementById('date').value;
    const description = document.getElementById('description').value.trim();

    // التحقق من ملء الحقول المطلوبة
    if (!name || !phone || !address || !service || !date || !description) {
        alert('⚠️ الرجاء ملء جميع الحقول المطلوبة');
        return;
    }

    // التحقق من صحة رقم الجوال
    if (!validatePhone(phone)) {
        alert('⚠️ الرجاء إدخال رقم جوال صحيح (مثال: 0512345678)');
        return;
    }

    // إنشاء طلب جديد
    const newRequest = {
        id: Date.now(),
        name: name,
        phone: phone,
        email: email,
        address: address,
        service: service,
        date: date,
        description: description,
        status: 'pending',
        createdAt: new Date().toLocaleString('ar-SA', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        })
    };

    // إضافة الطلب إلى المصفوفة
    requests.unshift(newRequest);

    // مسح الحقول
    clearForm();

    // عرض رسالة نجاح
    alert('✅ تم إرسال الطلب بنجاح!\nسنتواصل معك قريباً.');

    // الانتقال إلى صفحة التتبع
    showPage('track');
}

/**
 * دالة للتحقق من صحة رقم الجوال
 * @param {string} phone - رقم الجوال
 * @returns {boolean}
 */
function validatePhone(phone) {
    const phoneRegex = /^(05|5)[0-9]{8}$/;
    return phoneRegex.test(phone);
}

/**
 * دالة لمسح حقول النموذج
 */
function clearForm() {
    document.getElementById('name').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('email').value = '';
    document.getElementById('address').value = '';
    document.getElementById('service').value = '';
    document.getElementById('date').value = '';
    document.getElementById('description').value = '';
}

/**
 * دالة لعرض قائمة الطلبات
 */
function displayRequests() {
    const requestsList = document.getElementById('requestsList');

    // إذا لم تكن هناك طلبات
    if (requests.length === 0) {
        requestsList.innerHTML = `
            <div class="empty-state">
                <div class="icon">📋</div>
                <p>لا توجد طلبات حالياً</p>
                <button class="btn-primary" onclick="showPage('request')">إضافة طلب جديد</button>
            </div>
        `;
        return;
    }

    // بناء HTML للطلبات
    let html = '';
    requests.forEach(request => {
        const serviceData = services[request.service];
        const statusInfo = getStatusInfo(request.status);

        html += `
            <div class="request-card">
                <div class="request-header">
                    <div class="request-icon" style="background: ${serviceData.color};">
                        ${serviceData.icon}
                    </div>
                    <div class="request-content">
                        <h3>${serviceData.name}</h3>
                        <p class="request-description">${request.description}</p>
                        <div class="request-details">
                            <span>👤 ${request.name}</span>
                            <span>📱 ${request.phone}</span>
                            ${request.email ? `<span>✉️ ${request.email}</span>` : ''}
                            <span>📍 ${request.address}</span>
                            <span>📅 ${request.date}</span>
                        </div>
                        <div class="request-timestamp">تم الإنشاء: ${request.createdAt}</div>
                    </div>
                    <div class="request-status">
                        <span class="status-badge ${statusInfo.class}">
                            ${statusInfo.icon} ${statusInfo.text}
                        </span>
                        <select class="status-select" onchange="updateStatus(${request.id}, this.value)">
                            <option value="pending" ${request.status === 'pending' ? 'selected' : ''}>قيد الانتظار</option>
                            <option value="processing" ${request.status === 'processing' ? 'selected' : ''}>قيد المعالجة</option>
                            <option value="completed" ${request.status === 'completed' ? 'selected' : ''}>مكتمل</option>
                        </select>
                    </div>
                </div>
            </div>
        `;
    });

    requestsList.innerHTML = html;
}

/**
 * دالة للحصول على معلومات الحالة
 * @param {string} status - حالة الطلب
 * @returns {object}
 */
function getStatusInfo(status) {
    const statusMap = {
        pending: { 
            text: 'قيد الانتظار', 
            class: 'status-pending', 
            icon: '⏱️' 
        },
        processing: { 
            text: 'قيد المعالجة', 
            class: 'status-processing', 
            icon: '🔄' 
        },
        completed: { 
            text: 'مكتمل', 
            class: 'status-completed', 
            icon: '✅' 
        }
    };
    return statusMap[status];
}

/**
 * دالة لتحديث حالة الطلب
 * @param {number} id - معرف الطلب
 * @param {string} newStatus - الحالة الجديدة
 */
function updateStatus(id, newStatus) {
    // البحث عن الطلب وتحديث حالته
    const request = requests.find(req => req.id === id);
    if (request) {
        request.status = newStatus;
        displayRequests();
    }
}

/**
 * دالة لحذف طلب
 * @param {number} id - معرف الطلب
 */
function deleteRequest(id) {
    if (confirm('هل أنت متأكد من حذف هذا الطلب؟')) {
        requests = requests.filter(req => req.id !== id);
        displayRequests();
    }
}

// تحميل البيانات عند فتح الصفحة
window.addEventListener('DOMContentLoaded', function() {
    console.log('✅ تم تحميل الموقع بنجاح!');
    console.log('📱 موقع FixIt - خدمات الصيانة المنزلية');
});