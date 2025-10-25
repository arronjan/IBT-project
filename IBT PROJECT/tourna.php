<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

// Check if user is Admin - only admins can create tournaments
if ($_SESSION['role'] !== 'Admin') {
  header('Location: dashboard.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Tournament - IBT Badminton</title>
  <link rel="stylesheet" href="style.css" />
</head>

<body class="admin">
  <nav class="navbar">
    <div class="nav-container">
      <div class="nav-brand">
        <div class="logo-icon-small">🏸</div>
        <div>
          <h1 class="nav-title">IBT Badminton Center</h1>
          <p class="nav-subtitle">Sport Excellence</p>
        </div>
      </div>

      <button class="mobile-menu-btn" id="mobileMenuBtn">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <div class="nav-menu" id="navMenu">
        <a href="dashboard.php" class="nav-link">Dashboard</a>
        <a href="admin_reports.php" class="nav-link">Reports</a>
        <a href="admin_users.php" class="nav-link">Users</a>
        <a href="create_tourna.php" class="nav-link">Tournaments</a>
        <a href="history.php" class="nav-link">History</a>
        <button onclick="logout()" class="btn btn-logout">Logout</button>
      </div>
    </div>
  </nav>

  <main class="main-content">
    <div class="container">
      <div class="page-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
          <div>
            <h2>🏆 Create New Tournament</h2>
            <p>Set up a new tournament for your badminton court members</p>
          </div>
          <button onclick="window.location.href='create_tourna.php'" class="btn btn-secondary">
            ← Back to Tournaments
          </button>
        </div>
      </div>

      <div class="booking-layout">
        <!-- Tournament Form -->
        <div class="booking-main">
          <div class="card">
            <div class="card-title">
              <span class="icon">📋</span>
              Tournament Details
            </div>
            <form id="tournamentForm">
              <!-- Basic Information -->
              <div class="form-group">
                <label for="name">Tournament Name *</label>
                <input type="text" id="name" name="name" required placeholder="e.g., Summer Championship 2025" />
              </div>

              <!-- Date and Time -->
              <div class="form-row">
                <div class="form-group">
                  <label for="start_date">Start Date *</label>
                  <input type="date" id="start_date" name="start_date" required />
                </div>
                <div class="form-group">
                  <label for="end_date">End Date *</label>
                  <input type="date" id="end_date" name="end_date" required />
                </div>
              </div>

              <!-- Status -->
              <div class="form-group">
                <label for="status">Tournament Status *</label>
                <select id="status" name="status" required style="
                      width: 100%;
                      padding: 12px 16px;
                      border: 2px solid #e5e7eb;
                      border-radius: 10px;
                      font-size: 16px;
                      cursor: pointer;
                    ">
                  <option value="upcoming" selected>Upcoming</option>
                  <option value="ongoing">Ongoing</option>
                  <option value="completed">Completed</option>
                  <option value="cancelled">Cancelled</option>
                </select>
                <small style="color: #6b7280; display: block; margin-top: 4px;">
                  Status will automatically update based on dates
                </small>
              </div>

              <!-- Success/Error Messages -->
              <div id="formMessage"></div>

              <!-- Action Buttons -->
              <div class="button-group">
                <button type="submit" class="btn btn-primary">
                  <span>🏆</span> Create Tournament
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='create_tourna.php'">
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Preview Sidebar -->
        <div class="booking-sidebar">
          <div class="card">
            <div class="card-title">
              <span class="icon">👁️</span>
              Preview
            </div>
            <div id="tournamentPreview">
              <div class="empty-state">
                <div class="empty-icon">🏆</div>
                <p>Fill in the form to see a preview</p>
              </div>
            </div>
          </div>

          <!-- Info Card -->
          <div class="card" style="margin-top: 16px;">
            <div class="card-title">
              <span class="icon">ℹ️</span>
              Tips
            </div>
            <div style="padding: 16px; background: #f9fafb; border-radius: 8px;">
              <ul style="margin: 0; padding-left: 20px; color: #6b7280; font-size: 14px; line-height: 1.8;">
                <li>Choose clear, descriptive tournament names</li>
                <li>Set realistic date ranges for the tournament</li>
                <li>Status automatically updates based on start/end dates</li>
                <li>Members can join tournaments marked as "Upcoming"</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="main.js"></script>
  <script>
    // Verify admin access
    const currentUser = checkAuth();
    if (!currentUser || currentUser.role !== 'Admin') {
      window.location.href = 'dashboard.php';
    }

    // Set minimum dates
    const today = new Date().toISOString().split("T")[0];
    document.getElementById("start_date").min = today;
    document.getElementById("end_date").min = today;

    // Update end date minimum when start date changes
    document
      .getElementById("start_date")
      .addEventListener("change", function () {
        document.getElementById("end_date").min = this.value;
        updatePreview();
      });

    // Live preview update
    document.getElementById("name").addEventListener("input", updatePreview);
    document
      .getElementById("start_date")
      .addEventListener("change", updatePreview);
    document
      .getElementById("end_date")
      .addEventListener("change", updatePreview);
    document
      .getElementById("status")
      .addEventListener("change", updatePreview);

    function updatePreview() {
      const name = document.getElementById("name").value;
      const startDate = document.getElementById("start_date").value;
      const endDate = document.getElementById("end_date").value;
      const status = document.getElementById("status").value;

      if (!name) {
        document.getElementById("tournamentPreview").innerHTML = `
            <div class="empty-state">
              <div class="empty-icon">🏆</div>
              <p>Fill in the form to see a preview</p>
            </div>
          `;
        return;
      }

      const statusBadges = {
        upcoming: "badge-pending",
        ongoing: "badge-confirmed",
        completed: "badge-cancelled",
        cancelled: "badge-cancelled",
      };

      const statusLabels = {
        upcoming: "Upcoming",
        ongoing: "Ongoing",
        completed: "Completed",
        cancelled: "Cancelled",
      };

      const statusIcons = {
        upcoming: "📅",
        ongoing: "🎮",
        completed: "🏆",
        cancelled: "❌",
      };

      document.getElementById("tournamentPreview").innerHTML = `
          <div class="summary-card">
            <h3 style="margin-bottom: 12px; font-size: 18px;">
              ${statusIcons[status] || "🏆"} ${name}
            </h3>
            <span class="badge ${statusBadges[status] || "badge-pending"}">
              ${statusLabels[status] || status}
            </span>
          </div>
          <div class="summary-details">
            ${startDate
          ? `
              <div class="summary-row">
                <span style="color: #6b7280;">Start Date:</span>
                <strong>${new Date(startDate + 'T00:00:00').toLocaleDateString()}</strong>
              </div>
            `
          : ""
        }
            ${endDate
          ? `
              <div class="summary-row">
                <span style="color: #6b7280;">End Date:</span>
                <strong>${new Date(endDate + 'T00:00:00').toLocaleDateString()}</strong>
              </div>
            `
          : ""
        }
            ${startDate && endDate
          ? `
              <div class="summary-row">
                <span style="color: #6b7280;">Duration:</span>
                <strong>${calculateDuration(startDate, endDate)}</strong>
              </div>
            `
          : ""
        }
          </div>
        `;
    }

    function calculateDuration(startDate, endDate) {
      const start = new Date(startDate + 'T00:00:00');
      const end = new Date(endDate + 'T00:00:00');
      const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
      return days === 1 ? "1 day" : `${days} days`;
    }

    // Form submission
    document
      .getElementById("tournamentForm")
      .addEventListener("submit", async function (e) {
        e.preventDefault();

        const formData = {
          name: document.getElementById("name").value,
          start_date: document.getElementById("start_date").value,
          end_date: document.getElementById("end_date").value,
          status: document.getElementById("status").value,
        };

        // Validate dates
        const startDate = new Date(formData.start_date);
        const endDate = new Date(formData.end_date);

        if (endDate < startDate) {
          showMessage("End date must be after start date", "error");
          return;
        }

        try {
          showLoading();
          const response = await fetch("create_tournament.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(formData),
          });

          const result = await response.json();
          hideLoading();

          if (result.success) {
            showNotification("Tournament created successfully!", "success");
            setTimeout(() => {
              window.location.href = "create_tourna.php";
            }, 1500);
          } else {
            showNotification(
              result.message || "Failed to create tournament",
              "error"
            );
          }
        } catch (error) {
          hideLoading();
          showNotification("An error occurred. Please try again.", "error");
          console.error("Error:", error);
        }
      });

    function showMessage(message, type) {
      const messageDiv = document.getElementById("formMessage");
      messageDiv.className =
        type === "success" ? "success-message" : "error-message";
      messageDiv.textContent = message;
      messageDiv.style.display = "block";

      setTimeout(() => {
        messageDiv.style.display = "none";
      }, 5000);
    }
  </script>

  <style>
    .empty-state {
      text-align: center;
      padding: 40px 20px;
    }

    .empty-icon {
      font-size: 48px;
      margin-bottom: 16px;
    }

    .empty-state p {
      color: #9ca3af;
      font-size: 14px;
    }

    .summary-card {
      padding: 20px;
      background: #f9fafb;
      border-radius: 12px;
      margin-bottom: 16px;
    }

    .summary-details {
      padding: 16px;
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      padding: 12px 0;
      border-bottom: 1px solid #f3f4f6;
    }

    .summary-row:last-child {
      border-bottom: none;
    }

    #formMessage {
      display: none;
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 16px;
      font-size: 14px;
    }

    .success-message {
      background: #ecfdf5;
      color: #065f46;
      border: 1px solid #10b981;
    }

    .error-message {
      background: #fef2f2;
      color: #991b1b;
      border: 1px solid #ef4444;
    }

    @media (max-width: 768px) {
      .booking-layout {
        flex-direction: column;
      }

      .booking-main,
      .booking-sidebar {
        width: 100%;
      }
    }
  </style>
</body>

</html>