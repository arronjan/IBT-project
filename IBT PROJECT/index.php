<?php
session_start();
if (isset($_SESSION['user_id'])) {
  header('Location: dashboard.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>IBT Badminton - Login</title>
  <link rel="stylesheet" href="style.css" />
</head>

<body>
  <div class="login-container">
    <h2>Welcome to IBT Badminton Center</h2>
    <p>Sign in to manage your bookings</p>

    <form id="loginForm">
      <label>Email Address</label>
      <input type="email" id="email" name="email" required />

      <label>Password</label>
      <div class="password-wrapper">
        <input type="password" id="password" name="password" required />
        <span id="togglePassword" style="cursor:pointer;">👁️</span>
      </div>

      <button type="submit" id="loginBtn">
        <span id="loginText">Sign In</span>
        <span id="loadingSpinner" style="display:none;">⏳</span>
      </button>
    </form>

    <p>Don't have an account? <a href="register.php">Sign up</a></p>
  </div>

  <script>
    document.getElementById('togglePassword').addEventListener('click', () => {
      const pass = document.getElementById('password');
      pass.type = pass.type === 'password' ? 'text' : 'password';
    });

    document.getElementById('loginForm').addEventListener('submit', async (e) => {
      e.preventDefault();

      const btn = document.getElementById('loginBtn');
      const spinner = document.getElementById('loadingSpinner');
      const text = document.getElementById('loginText');

      spinner.style.display = 'inline-block';
      text.style.display = 'none';
      btn.disabled = true;

      const formData = new FormData(e.target);

      try {
        const res = await fetch('login.php', { method: 'POST', body: formData });
        const result = await res.json();

        if (result.success) {
          // FIXED: Store user data in sessionStorage
          sessionStorage.setItem('user', JSON.stringify(result.user));

          alert('✓ ' + result.message);
          window.location.href = 'dashboard.php';
        } else {
          alert('✕ ' + result.message);
          spinner.style.display = 'none';
          text.style.display = 'inline';
          btn.disabled = false;
        }
      } catch (err) {
        console.error(err);
        alert('Connection error. Please try again.');
        spinner.style.display = 'none';
        text.style.display = 'inline';
        btn.disabled = false;
      }
    });
  </script>
</body>

</html>