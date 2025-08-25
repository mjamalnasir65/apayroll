// Check authentication on page load
checkAuth();

// Global variables
let currentUserData = null;
let salaryHistory = [];

// Load user data and salary history on page load
document.addEventListener('DOMContentLoaded', async () => {
    await loadUserData();
    await loadSalaryHistory();
    setupEventListeners();
});

// Load user data
async function loadUserData() {
    try {
        const response = await fetch('/api/worker/profile.php', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentUserData = data.worker;
            updateSubscriptionUI(currentUserData);
            document.getElementById('expectedSalary').value = currentUserData.expected_salary || '';
        } else {
            alert('Failed to load profile data');
        }
    } catch (error) {
        console.error('Error loading profile:', error);
        alert('Error loading profile data');
    }
}

// Update subscription UI
function updateSubscriptionUI(userData) {
    const statusElement = document.getElementById('subStatus');
    const expiryElement = document.getElementById('subExpiry');
    const renewBtn = document.getElementById('renewBtn');
    
    statusElement.textContent = userData.subscription_status;
    statusElement.className = getStatusClass(userData.subscription_status);
    
    if (userData.subscription_expiry) {
        const expiryDate = new Date(userData.subscription_expiry);
        expiryElement.textContent = expiryDate.toLocaleDateString();
        
        // Show renew button if expiring within 30 days
        const daysUntilExpiry = Math.floor((expiryDate - new Date()) / (1000 * 60 * 60 * 24));
        if (daysUntilExpiry <= 30) {
            renewBtn.classList.remove('hidden');
        }
    } else {
        expiryElement.textContent = 'Not subscribed';
        renewBtn.classList.remove('hidden');
    }
}

// Load salary history
async function loadSalaryHistory() {
    try {
        const response = await fetch('/api/worker/salary-history.php', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            salaryHistory = data.history;
            updateSalaryHistoryTable();
        }
    } catch (error) {
        console.error('Error loading salary history:', error);
    }
}

// Update salary history table
function updateSalaryHistoryTable() {
    const tableBody = document.getElementById('salaryHistory');
    tableBody.innerHTML = '';
    
    salaryHistory.forEach(record => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">${formatMonth(record.month)}</td>
            <td class="px-6 py-4 whitespace-nowrap">RM ${record.expected_amount}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 rounded ${getStatusClass(record.status)}">
                    ${record.status}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                ${record.receipt_url ? 
                    `<a href="${record.receipt_url}" target="_blank" class="text-blue-600 hover:underline">View</a>` : 
                    'Not uploaded'}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                ${record.status !== 'received' ? 
                    `<button onclick="showUploadModal('${record.month}')" class="text-blue-600 hover:underline">
                        Upload Receipt
                    </button>` : 
                    ''}
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// Setup event listeners
function setupEventListeners() {
    // Expected salary input
    document.getElementById('expectedSalary').addEventListener('change', updateExpectedSalary);
    
    // Renew subscription button
    document.getElementById('renewBtn').addEventListener('click', initiateSubscriptionRenewal);
    
    // Upload form
    document.getElementById('uploadForm').addEventListener('submit', handleReceiptUpload);
}

// Handle receipt upload
async function handleReceiptUpload(e) {
    e.preventDefault();
    
    const month = document.getElementById('uploadMonth').value;
    const file = document.getElementById('receiptFile').files[0];
    
    if (!file) {
        alert('Please select a file');
        return;
    }
    
    const formData = new FormData();
    formData.append('receipt', file);
    formData.append('month', month);
    
    try {
        const response = await fetch('/api/worker/upload-receipt.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeUploadModal();
            await loadSalaryHistory(); // Refresh the table
        } else {
            alert(data.message || 'Upload failed');
        }
    } catch (error) {
        console.error('Upload error:', error);
        alert('Upload failed');
    }
}

// Show upload modal
function showUploadModal(month) {
    document.getElementById('uploadMonth').value = month;
    document.getElementById('uploadModal').classList.remove('hidden');
}

// Close upload modal
function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
    document.getElementById('uploadForm').reset();
}

// Update expected salary
async function updateExpectedSalary(e) {
    const amount = e.target.value;
    
    try {
        const response = await fetch('/api/worker/update-salary.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ expected_amount: amount })
        });
        
        const data = await response.json();
        
        if (!data.success) {
            alert(data.message || 'Failed to update salary');
            e.target.value = currentUserData.expected_salary || '';
        }
    } catch (error) {
        console.error('Error updating salary:', error);
        alert('Failed to update salary');
        e.target.value = currentUserData.expected_salary || '';
    }
}

// Initiate subscription renewal
async function initiateSubscriptionRenewal() {
    try {
        const response = await fetch('/api/worker/renew-subscription.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Redirect to payment gateway
            window.location.href = data.payment_url;
        } else {
            alert(data.message || 'Failed to initiate renewal');
        }
    } catch (error) {
        console.error('Renewal error:', error);
        alert('Failed to initiate renewal');
    }
}

// Helper functions
function formatMonth(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
}

function getStatusClass(status) {
    switch (status) {
        case 'active':
        case 'received':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'expired':
        case 'disputed':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

// Logout function
function logout() {
    localStorage.removeItem('token');
    window.location.href = '/index.html';
}
