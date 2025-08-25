// Check if PWA is installed
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('ServiceWorker registration successful');
            })
            .catch(err => {
                console.log('ServiceWorker registration failed: ', err);
            });
    });
}

// Form toggle functionality
function toggleForms() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    loginForm.classList.toggle('hidden');
    registerForm.classList.toggle('hidden');
}

// Handle login form submission
document.querySelector('#loginForm form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const passport = document.getElementById('loginPassport').value;
    const password = document.getElementById('loginPassword').value;

    try {
        const response = await fetch('/api/auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                passport_no: passport,
                password: password
            })
        });

        const data = await response.json();

        if (data.success) {
            // Store the token
            localStorage.setItem('token', data.token);
            // Redirect to dashboard
            window.location.href = '/dashboard.html';
        } else {
            alert(data.message || 'Login failed');
        }
    } catch (error) {
        console.error('Login error:', error);
        alert('Login failed. Please try again.');
    }
});

// Handle registration form submission
document.querySelector('#registerForm form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = {
        full_name: document.getElementById('regName').value,
        passport_no: document.getElementById('regPassport').value,
        nationality: document.getElementById('regNationality').value,
        wallet_id: document.getElementById('regWallet').value,
        permit_expiry: document.getElementById('regPermitExpiry').value,
        password: document.getElementById('regPassword').value
    };

    try {
        const response = await fetch('/api/auth/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            alert('Registration successful! Please login.');
            toggleForms(); // Switch to login form
        } else {
            alert(data.message || 'Registration failed');
        }
    } catch (error) {
        console.error('Registration error:', error);
        alert('Registration failed. Please try again.');
    }
});

// Auth token checker
function checkAuth() {
    const token = localStorage.getItem('token');
    if (!token && !window.location.pathname.includes('index.html')) {
        window.location.href = '/index.html';
    }
}

// Check auth on page load
checkAuth();
