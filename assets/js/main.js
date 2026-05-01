document.addEventListener('DOMContentLoaded', function() {
    initNavbar();
    initModals();
    initFormValidation();
    initDropdowns();
    initTabs();
    initDarkMode();
    initPageAnimations();
});

function initNavbar() {
    const mobileToggle = document.querySelector('.mobile-toggle');
    const navbarNav = document.querySelector('.navbar-nav');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (mobileToggle && navbarNav) {
        mobileToggle.addEventListener('click', () => {
            navbarNav.classList.toggle('show');
        });
    }

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }
}

function initModals() {
    document.querySelectorAll('[data-modal-target]').forEach(trigger => {
        trigger.addEventListener('click', () => {
            const modalId = trigger.getAttribute('data-modal-target');
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('show');
        });
    });

    document.querySelectorAll('.modal-close, .modal-overlay').forEach(el => {
        el.addEventListener('click', (e) => {
            if (e.target === el) {
                document.querySelectorAll('.modal-overlay.show').forEach(modal => {
                    modal.classList.remove('show');
                });
            }
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.show').forEach(modal => {
                modal.classList.remove('show');
            });
        }
    });
}

function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    let errorEl = field.parentElement.querySelector('.form-error');
                    if (!errorEl) {
                        errorEl = document.createElement('div');
                        errorEl.className = 'form-error';
                        field.parentElement.appendChild(errorEl);
                    }
                    errorEl.textContent = 'This field is required';
                } else {
                    field.classList.remove('error');
                    const errorEl = field.parentElement.querySelector('.form-error');
                    if (errorEl) errorEl.remove();
                }
            });

            const emailFields = form.querySelectorAll('[type="email"]');
            emailFields.forEach(field => {
                if (field.value && !isValidEmail(field.value)) {
                    isValid = false;
                    field.classList.add('error');
                    let errorEl = field.parentElement.querySelector('.form-error');
                    if (!errorEl) {
                        errorEl = document.createElement('div');
                        errorEl.className = 'form-error';
                        field.parentElement.appendChild(errorEl);
                    }
                    errorEl.textContent = 'Please enter a valid email address';
                }
            });

            if (!isValid) e.preventDefault();
        });
    });
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function initDropdowns() {
    document.querySelectorAll('.user-menu').forEach(menu => {
        const trigger = menu.querySelector('[data-dropdown-toggle]');
        const dropdown = menu.querySelector('.dropdown-menu');

        if (trigger && dropdown) {
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('show');
            });

            document.addEventListener('click', () => {
                dropdown.classList.remove('show');
            });
        }
    });
}

function initTabs() {
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const tabGroup = tab.closest('.tabs');
            if (!tabGroup) return;

            tabGroup.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            const targetId = tab.getAttribute('data-tab');
            if (targetId) {
                const tabContainer = tabGroup.closest('.tab-container');
                if (tabContainer) {
                    tabContainer.querySelectorAll('.tab-content').forEach(content => {
                        content.style.display = content.id === targetId ? 'block' : 'none';
                    });
                }
            }
        });
    });
}

function confirmDelete(message, callback) {
    if (confirm(message || 'Are you sure you want to delete this item?')) {
        callback();
    }
}

function showToast(message, type = 'success') {
    const container = document.querySelector('.toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function filterTable(searchInput, tableId) {
    const searchTerm = searchInput.value.toLowerCase();
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

function printPage() {
    window.print();
}

function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => {
            rowData.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });

    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function initDarkMode() {
    const toggleBtn = document.querySelector('.dark-mode-toggle');
    if (!toggleBtn) return;

    const savedMode = localStorage.getItem('darkMode');
    if (savedMode === 'true') {
        document.body.classList.add('dark-mode');
    }

    toggleBtn.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDark);
        
        toggleBtn.style.transform = 'scale(0.8) rotate(180deg)';
        setTimeout(() => {
            toggleBtn.style.transform = '';
        }, 300);
    });
}

function initPageAnimations() {
    const mainContainer = document.querySelector('.container');
    if (mainContainer) {
        mainContainer.classList.add('page-enter');
    }
    
    const cards = document.querySelectorAll('.card, .event-card, .stat-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                entry.target.style.animation = `pageFadeIn 0.4s ease ${index * 0.1}s both`;
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    cards.forEach(card => observer.observe(card));
}

function showLoginAnimation() {
    const overlay = document.createElement('div');
    overlay.className = 'login-success-overlay';
    overlay.innerHTML = `
        <div class="login-success-content">
            <div class="login-checkmark">
                <svg viewBox="0 0 52 52">
                    <path d="M14 27 l8 8 l16-16" />
                </svg>
            </div>
            <div class="login-success-text">Welcome back!</div>
            <div class="login-success-subtext">Redirecting to dashboard...</div>
        </div>
    `;
    
    const confettiContainer = document.createElement('div');
    confettiContainer.className = 'confetti-container';
    
    document.body.appendChild(overlay);
    document.body.appendChild(confettiContainer);
    
    createConfetti(confettiContainer);
    
    setTimeout(() => {
        overlay.style.opacity = '0';
        overlay.style.transition = 'opacity 0.3s ease';
        confettiContainer.remove();
    }, 1700);
    
    setTimeout(() => {
        overlay.remove();
    }, 2000);
}

function createConfetti(container) {
    const colors = ['#6366f1', '#818cf8', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#ec4899', '#8b5cf6'];
    
    for (let i = 0; i < 60; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.top = '-10px';
        confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.width = (Math.random() * 8 + 5) + 'px';
        confetti.style.height = (Math.random() * 8 + 5) + 'px';
        confetti.style.animationDelay = Math.random() * 0.5 + 's';
        confetti.style.animationDuration = (Math.random() * 1 + 1) + 's';
        container.appendChild(confetti);
    }
}
