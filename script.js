let currentUser = null;

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


// Toast Notification

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastIcon = toast.querySelector('.toast-icon');
    const toastMessage = toast.querySelector('.toast-message');
    
    const icons = {
        success: '✓',
        error: '✕'
    };
    
    toastIcon.textContent = icons[type] || '✓';
    toastMessage.textContent = message;
    toast.className = `toast ${type}`;
    
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => toast.classList.remove('show'), 3500);
}


// Navigation & Pages

function showPage(pageName) {
    // التحقق من تسجيل الدخول للصفحات المحمية
    if ((pageName === 'request' || pageName === 'track') && !currentUser) {
        showPage('auth');
        showToast('يجب تسجيل الدخول أولاً', 'error');
        return;
    }

    const pages = document.querySelectorAll('.page');
    pages.forEach(page => page.classList.remove('active'));
    
    const selectedPage = document.getElementById(pageName);
    if (selectedPage) {
        selectedPage.classList.add('active');
    }
    
    const navButtons = document.querySelectorAll('.nav-btn');
    navButtons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.page === pageName) {
            btn.classList.add('active');
        }
    });

    const navLinks = document.querySelector('.nav-links');
    if (navLinks) {
        navLinks.classList.remove('active');
    }

    // تحميل البيانات حسب الصفحة
    if (pageName === 'track' && currentUser) {
        loadUserRequests();
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function toggleMenu() {
    const navLinks = document.querySelector('.nav-links');
    navLinks.classList.toggle('active');
}

function selectService(serviceType) {
    if (!currentUser) {
        showPage('auth');
        showToast('يجب تسجيل الدخول أولاً', 'error');
        return;
    }
    showPage('request');
    setTimeout(() => {
        const serviceSelect = document.getElementById('service');
        if (serviceSelect) {
            serviceSelect.value = serviceType;
        }
    }, 300);
}

function requestService() {
    if (!currentUser) {
        showPage('auth');
        showToast('يجب تسجيل الدخول أولاً', 'error');
        return;
    }
    showPage('request');
}


// Authentication Functions

function updateAuthUI() {
    const authButtons = document.getElementById('authButtons');
    
    if (currentUser) {
        authButtons.innerHTML = `
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <span style="color:var(--text-secondary);font-size:14px;">مرحباً، ${currentUser.name}</span>
                <button onclick="handleLogout()" class="nav-btn">
                    <span class="nav-icon">🚪</span>
                    <span>تسجيل خروج</span>
                </button>
            </div>
        `;
    } else {
        authButtons.innerHTML = `
            <button onclick="showPage('auth')" class="nav-btn" data-page="auth">
                <span class="nav-icon">👤</span>
                <span>تسجيل دخول</span>
            </button>
        `;
    }
}

async function checkAuthStatus() {
    try {
        const response = await fetch('check_auth.php');
        const data = await response.json();
        if (data.logged_in) {
            currentUser = data.user;
            updateAuthUI();
        } else {
            currentUser = null;
            updateAuthUI();
        }
    } catch (error) {
        console.error('Error checking auth:', error);
        currentUser = null;
        updateAuthUI();
    }
}

function switchToRegister(e) {
    if (e) e.preventDefault();
    
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const authTitle = document.getElementById('authTitle');
    const authSubtitle = document.getElementById('authSubtitle');
    
    if (loginForm) loginForm.style.display = 'none';
    if (registerForm) registerForm.style.display = 'block';
    if (authTitle) authTitle.textContent = 'إنشاء حساب جديد';
    if (authSubtitle) authSubtitle.textContent = 'سجل الآن للوصول إلى جميع الخدمات';
}

function switchToLogin(e) {
    if (e) e.preventDefault();
    
    const registerForm = document.getElementById('registerForm');
    const loginForm = document.getElementById('loginForm');
    const authTitle = document.getElementById('authTitle');
    const authSubtitle = document.getElementById('authSubtitle');
    
    if (registerForm) registerForm.style.display = 'none';
    if (loginForm) loginForm.style.display = 'block';
    if (authTitle) authTitle.textContent = 'تسجيل الدخول';
    if (authSubtitle) authSubtitle.textContent = 'سجل دخولك للوصول إلى خدماتنا';
}

async function handleLogin(e) {
    e.preventDefault();
    
    const phone = document.getElementById('loginPhone').value.trim();
    const password = document.getElementById('loginPassword').value;
    
    if (!phone || !password) {
        showToast('يرجى إدخال رقم الجوال وكلمة المرور', 'error');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('phone', phone);
        formData.append('password', password);
        
        const response = await fetch('auth.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('تم تسجيل الدخول بنجاح', 'success');
            await checkAuthStatus();
            showPage('home');
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Login error:', error);
        showToast('حدث خطأ في تسجيل الدخول', 'error');
    }
}

async function handleRegister(e) {
    e.preventDefault();
    
    const name = document.getElementById('registerName').value.trim();
    const phone = document.getElementById('registerPhone').value.trim();
    const email = document.getElementById('registerEmail').value.trim();
    const password = document.getElementById('registerPassword').value;
    
    if (!name || !phone || !password) {
        showToast('يرجى ملء جميع الحقول المطلوبة', 'error');
        return;
    }
    
    if (!/^(05|5)[0-9]{8}$/.test(phone.replace(/[^0-9]/g, ''))) {
        showToast('رقم الجوال غير صحيح (يجب أن يبدأ بـ 05 ويتكون من 10 أرقام)', 'error');
        return;
    }
    
    if (password.length < 6) {
        showToast('كلمة المرور يجب أن تكون 6 أحرف على الأقل', 'error');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'register');
        formData.append('name', name);
        formData.append('phone', phone);
        formData.append('email', email);
        formData.append('password', password);
        
        const response = await fetch('auth.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('تم إنشاء الحساب بنجاح', 'success');
            await checkAuthStatus();
            showPage('home');
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Register error:', error);
        showToast('حدث خطأ في إنشاء الحساب', 'error');
    }
}

async function handleLogout() {
    try {
        const formData = new FormData();
        formData.append('action', 'logout');
        
        await fetch('auth.php', {
            method: 'POST',
            body: formData
        });
        
        currentUser = null;
        updateAuthUI();
        showToast('تم تسجيل الخروج بنجاح', 'success');
        showPage('home');
    } catch (error) {
        console.error('Logout error:', error);
        showToast('حدث خطأ في تسجيل الخروج', 'error');
    }
}


// Request Functions

async function handleFormSubmit(e) {
    e.preventDefault();
    
    if (!currentUser) {
        showToast('يجب تسجيل الدخول أولاً', 'error');
        showPage('auth');
        return;
    }
    
    const address = document.getElementById('address').value.trim();
    const service = document.getElementById('service').value;
    const date = document.getElementById('date').value;
    const description = document.getElementById('description').value.trim();

    if (!address || !service || !date || !description) {
        showToast('⚠️ الرجاء ملء جميع الحقول المطلوبة', 'error');
        return;
    }

    const selectedDate = new Date(date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (selectedDate < today) {
        showToast('⚠️ الرجاء اختيار تاريخ في المستقبل', 'error');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('address', address);
        formData.append('service', service);
        formData.append('date', date);
        formData.append('description', description);

        const response = await fetch('submit_request.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('✅ تم إرسال الطلب بنجاح! سنتواصل معك قريباً', 'success');
            document.getElementById('requestForm').reset();
            
            setTimeout(() => {
                showPage('track');
            }, 1500);
        } else {
            showToast('❌ ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Submit error:', error);
        showToast('❌ حدث خطأ في إرسال الطلب', 'error');
    }
}


// Track Requests

async function loadUserRequests() {
    if (!currentUser) {
        document.getElementById('requestsList').innerHTML = `
            <div class="empty-state">
                <div class="icon">🔒</div>
                <p>يجب تسجيل الدخول أولاً</p>
                <button class="btn-primary" onclick="showPage('auth')">
                    <span>تسجيل الدخول</span>
                    <span class="btn-arrow">←</span>
                </button>
            </div>
        `;
        return;
    }

    try {
        const response = await fetch('track_api.php');
        const result = await response.json();
        
        if (result.success) {
            displayUserRequests(result.data);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Load requests error:', error);
        showToast('حدث خطأ في تحميل الطلبات', 'error');
    }
}

function displayUserRequests(requests) {
    const requestsList = document.getElementById('requestsList');
    
    if (requests.length === 0) {
        requestsList.innerHTML = `
            <div class="empty-state">
                <div class="icon">🔭</div>
                <p>لا توجد طلبات حالياً</p>
                <p style="font-size: 16px;">ابدأ بطلب خدمة جديدة</p>
                <button class="btn-primary" onclick="showPage('request')">
                    <span>إضافة طلب جديد</span>
                    <span class="btn-arrow">←</span>
                </button>
            </div>
        `;
        return;
    }

    let html = '';
    requests.forEach((request, index) => {
        const serviceData = services[request.service_type] || services.general;
        const statusInfo = getStatusInfo(request.status);

        html += `
            <div class="request-card" style="animation-delay: ${index * 0.1}s">
                <div class="request-header">
                    <div class="request-icon" style="background: ${serviceData.color};">
                        ${serviceData.icon}
                    </div>
                    <div class="request-content">
                        <h3>${serviceData.name} - طلب #${request.id}</h3>
                        <p class="request-description">${request.description}</p>
                        <div class="request-details">
                            <span>📍 ${request.address}</span>
                            <span>📅 ${request.preferred_date}</span>
                        </div>
                        <div class="request-timestamp">تم الإنشاء: ${request.created_at}</div>
                    </div>
                    <div class="request-actions">
                        <span class="status-badge ${statusInfo.class}">
                            ${statusInfo.icon} ${statusInfo.text}
                        </span>
                    </div>
                </div>
            </div>
        `;
    });

    requestsList.innerHTML = html;
}

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
    return statusMap[status] || statusMap.pending;
}


// Event Listeners

function initializeEventListeners() {
    // Request form
    const requestForm = document.getElementById('requestForm');
    if (requestForm) {
        requestForm.addEventListener('submit', handleFormSubmit);
    }

    // Login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    // Register form
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }

    // Date input min
    const dateInput = document.getElementById('date');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }

    // Navbar scroll effect
    window.addEventListener('scroll', () => {
        const navbar = document.getElementById('navbar');
        const currentScroll = window.pageYOffset;

        if (currentScroll > 100) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Close menu on outside click
    document.addEventListener('click', (e) => {
        const navLinks = document.querySelector('.nav-links');
        const menuToggle = document.querySelector('.menu-toggle');
        
        if (navLinks && menuToggle) {
            if (!navLinks.contains(e.target) && !menuToggle.contains(e.target)) {
                navLinks.classList.remove('active');
            }
        }
    });
}