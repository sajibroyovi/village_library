// Helper to make API calls to auth.php
async function authCall(action, data = {}) {
    const formData = new URLSearchParams();
    formData.append('action', action);
    for (const key in data) {
        formData.append(key, data[key]);
    }
    const response = await fetch('auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    });
    return response.json();
}

// Helper to make API calls to api.php (for CRUD)
async function apiCall(action, data = {}) {
    let body;
    // Robust check for FormData
    const isFormData = data && typeof data.append === 'function' && 
                       (data instanceof FormData || Object.prototype.toString.call(data) === '[object FormData]');
    
    if (isFormData) {
        body = data;
        if (!body.has('action')) body.append('action', action);
    } else {
        body = new URLSearchParams();
        body.append('action', action);
        for (const key in data) {
            body.append(key, data[key]);
        }
    }
    
    const options = {
        method: 'POST',
        body: isFormData ? body : body.toString()
    };
    
    if (!isFormData) {
        options.headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
    }
    
    const response = await fetch('api.php', options);
    return response.json();
}

// Reusable alert display function
function showAlert(elementId, message, type = 'error') {
    const el = document.getElementById(elementId);
    if (!el) return;
    el.className = `alert ${type}`;
    el.innerText = message;
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        el.style.display = 'none';
    }, 5000);
}
