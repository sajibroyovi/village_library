<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Family Information Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="glass-card">
            <h1 class="title">Welcome Back</h1>
            <p class="subtitle">Family Information Portal</p>

            <div id="loginAlert" class="alert"></div>

            <form id="loginForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" class="form-control" autocomplete="off" required placeholder="Enter your username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" class="form-control" required placeholder="••••••••">
                </div>
                
                <button type="submit" class="btn-primary" id="loginBtn">Secure Login</button>
            </form>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const btn = document.getElementById('loginBtn');
            const alertBox = document.getElementById('loginAlert');
            
            btn.innerHTML = 'Connecting...';
            btn.disabled = true;
            
            try {
                const res = await authCall('login', { username, password });
                
                if (res.status === 'success') {
                    alertBox.className = 'alert success';
                    alertBox.style.display = 'block';
                    alertBox.innerHTML = 'Login successful! Redirecting...';
                    
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1000);
                } else {
                    alertBox.className = 'alert error';
                    alertBox.style.display = 'block';
                    alertBox.innerHTML = res.message || 'Invalid credentials.';
                    btn.innerHTML = 'Secure Login';
                    btn.disabled = false;
                }
            } catch (error) {
                alertBox.className = 'alert error';
                alertBox.style.display = 'block';
                alertBox.innerHTML = 'Connection error. Please try again.';
                btn.innerHTML = 'Secure Login';
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
