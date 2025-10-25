<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: index.html');
  exit;
}

// Check if user is Admin - only admins can edit tournaments
if ($_SESSION['role'] !== 'Admin') {
  header('Location: dashboard.php');
  exit;
}

// Check if tournament ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  header('Location: create_tourna.php');
  exit;
}

$tournamentId = $_GET['id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Tournament - IBT Badminton</title>
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
        <a href="create_tourna.php" class="nav-link active">Tournaments</a>
        <a href="history.html" class="nav-link">History</a>
        <button onclick="logout()" class="btn btn-logout">Logout</button>
      </div>
    </div>
  </nav>

  <main class="main-content">
    <div class="container">
      <div class="page-header">
        <h2>✏️ Edit Tournament</h2>
        <p>Update tournament information</p>
      </div>

      <div class="booking-layout">
        <!-- Tournament Form -->
        <div class="booking-main">
          <div class="card">
            <div class="card-title">
              <span class="icon">📋</span>
              Tournament Details
            </div>

            <div id="loadingState" style="text-align: center; padding: 40px">
              <div class="spinner"></div>
              <p style="margin-top: 16px; color: #6b7280">
                Loading tournament...
              </p>
            </div>

            <form id="tournamentForm" style="display: none">
              <input type="hidden" id="tournament_id" name="tournament_id"
                value="<?php echo htmlspecialchars($tournamentId); ?>" />

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
                  <option value="upcoming">Upcoming</option>
                  <option value="ongoing">Ongoing</option>
                  <option value="completed">Completed</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>

              <!-- Success/Error Messages -->
              <div id="formMessage"></div>

              <!-- Action Buttons -->
              <div class="button-group">
                <button type="submit" class="btn btn-primary">
                  <span>💾</span> Save Changes
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
                <p>Loading preview...</p>
              </div>
            </div>
          </div>

          <!-- Tournament Info Card -->
          <div class="card" style="margin-top: 16px">
            <div class="card-title">
              <span class="icon">ℹ️</span>
              Information
            </div>
            <div style="padding: 16px; background: #f9fafb; border-radius: 8px;">
              <p style="font-size: 14px; color: #6b7280; margin-bottom: 12px;">
                <strong style="color: #374151;">Tournament ID:</strong> <?php echo htmlspecialchars($tournamentId); ?>
              </p>
              <p style="font-size: 14px; color: #6b7280; margin: 0;">
                Changes will be saved immediately and visible to all users.
              </p>
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

    const tournamentId = <?php echo json_encode($tournamentId); ?>;

    // Load tournament data
    loadTournamentData();

    async function loadTournamentData() {
      try {
        showLoading();
        const response = await fetch(
          `get_tournament_by_id.php?id=${tournamentId}`
        );
        const data = await response.json();
        hideLoading();

        if (data.success) {
          const tournament = data.tournament;

          // Fill form
          document.getElementById("tournament_id").value =
            tournament.tournament_id;
          document.getElementById("name").value = tournament.name;
          document.getElementById("start_date").value = tournament.start_date;
          document.getElementById("end_date").value = tournament.end_date;
          document.getElementById("status").value = tournament.status;

          // Show form, hide loading
          document.getElementById("loadingState").style.display = "none";
          document.getElementById("tournamentForm").style.display = "block";

          // Update preview
          updatePreview();

          // Set minimum dates
          const today = new Date().toISOString().split("T")[0];
          document.getElementById("start_date").min = today;
          document.getElementById("end_date").min = today;
        } else {
          showNotification("Failed to load tournament: " + data.message, "error");
          setTimeout(() => {
            window.location.href = "create_tourna.php";
          }, 2000);
        }
      } catch (error) {
        hideLoading();
        console.error("Error:", error);
        showNotification("Error loading tournament", "error");
        setTimeout(() => {
          window.location.href = "create_tourna.php";
        }, 2000);
      }
    }

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
              <p>Fill in the form to see preview</p>
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
          tournament_id: document.getElementById("tournament_id").value,
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
          const response = await fetch("update_tournament.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(formData),
          });

          const result = await response.json();
          hideLoading();

          if (result.success) {
            showNotification("Tournament updated successfully!", "success");
            setTimeout(() => {
              window.location.href = "create_tourna.php";
            }, 1500);
          } else {
            showNotification(
              result.message || "Failed to update tournament",
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
    .spinner {
      width: 40px;
      height: 40px;
      border: 4px solid rgba(16, 185, 129, 0.3);
      border-top-color: #10b981;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 0 auto;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

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