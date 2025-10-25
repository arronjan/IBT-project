<?php
session_start();

// If user is already logged in, redirect to dashboard
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
  <title>IBT Badminton - Register</title>
  <link rel="stylesheet" href="style.css" />
</head>

<body class="login-page">
  <div class="login-container">
    <div class="login-grid">
      <!-- Left Side - Information -->
      <div class="login-info">
        <div class="logo-section">
          <div class="logo-icon">🏸</div>
          <div>
            <h1>IBT Badminton</h1>
            <p class="tagline">Sport Center Management</p>
          </div>
        </div>
        <p class="description">
          Join us today and experience seamless badminton court booking!
        </p>
        <div class="features">
          <div class="feature-item">
            <span class="check-icon"></span>
            <span>Quick registration process</span>
          </div>
          <div class="feature-item">
            <span class="check-icon"></span>
            <span>Instant booking access</span>
          </div>
          <div class="feature-item">
            <span class="check-icon"></span>
            <span>Join tournaments & events</span>
          </div>
          <div class="feature-item">
            <span class="check-icon"></span>
            <span>Track your booking history</span>
          </div>
        </div>
      </div>

      <!-- Right Side - Register Form -->
      <div class="login-form-container">
        <div class="login-form">
          <h2>Create Account</h2>
          <p class="subtitle">Sign up to start booking courts</p>

          <form id="registerForm">
            <div class="form-group">
              <label for="name">Full Name</label>
              <input type="text" id="name" name="name" placeholder="John Doe" required autocomplete="name" />
            </div>

            <div class="form-group">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email" placeholder="you@gmail.com" required autocomplete="email" />
            </div>

            <div class="form-group">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" placeholder="••••••••" required minlength="6"
                autocomplete="new-password" />
              <small style="color: #6b7280; display: block; margin-top: 4px;">
                Minimum 6 characters
              </small>
            </div>

            <div id="errorMessage" class="error-message" style="display: none"></div>

            <div class="button-group">
              <button type="submit" class="btn btn-primary" id="registerBtn">
                <span id="registerBtnText">Create Account</span>
                <span id="registerBtnSpinner" class="spinner" style="display: none"></span>
              </button>
            </div>

            <div class="form-footer">
              <p class="signup-text">
                Already have an account?
                <a href="index.php" class="link">Sign in</a>
              </p>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="main.js"></script>
  <script>
    // Client-side check if already logged in
    const user = sessionStorage.getItem("user");
    if (user) {
      window.location.href = "dashboard.php";
    }

    document
      .getElementById("registerForm")
      .addEventListener("submit", async (e) => {
        e.preventDefault();

        const name = document.getElementById("name").value;
        const email = document.getElementById("email").value;
        const password = document.getElementById("password").value;
        const errorDiv = document.getElementById("errorMessage");
        const registerBtn = document.getElementById("registerBtn");
        const btnText = document.getElementById("registerBtnText");
        const btnSpinner = document.getElementById("registerBtnSpinner");

        // Show loading state
        registerBtn.disabled = true;
        btnText.style.display = "none";
        btnSpinner.style.display = "inline-block";
        errorDiv.style.display = "none";

        try {
          const response = await fetch("register_process.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({ name, email, password }),
          });

          const data = await response.json();

          if (data.success) {
            // Show success message
            errorDiv.textContent = "✓ Account created! Redirecting to login...";
            errorDiv.style.display = "block";
            errorDiv.style.background = "#ecfdf5";
            errorDiv.style.color = "#065f46";
            errorDiv.style.border = "1px solid #10b981";

            // Redirect to login
            setTimeout(() => {
              window.location.href = "index.php";
            }, 1500);
          } else {
            // Show error message
            showError(data.message || "Registration failed");

            // Reset button
            registerBtn.disabled = false;
            btnText.style.display = "inline";
            btnSpinner.style.display = "none";
          }
        } catch (error) {
          console.error("Registration error:", error);
          showError("Connection error. Please try again.");

          // Reset button
          registerBtn.disabled = false;
          btnText.style.display = "inline";
          btnSpinner.style.display = "none";
        }
      });

    function showError(message) {
      const errorDiv = document.getElementById("errorMessage");
      errorDiv.textContent = "✕ " + message;
      errorDiv.style.display = "block";
      errorDiv.style.background = "#fef2f2";
      errorDiv.style.color = "#991b1b";
      errorDiv.style.border = "1px solid #ef4444";
    }
  </script>

  <style>
    .spinner {
      width: 16px;
      height: 16px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-top-color: white;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      display: inline-block;
      vertical-align: middle;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    .error-message {
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 16px;
      font-size: 14px;
      font-weight: 500;
      text-align: center;
    }

    #registerBtn:disabled {
      opacity: 0.7;
      cursor: not-allowed;
    }
  </style>
</body>

</html>