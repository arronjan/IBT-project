<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: index.html');
  exit;
}

// Check if user is Admin - only admins can view tournaments management
if ($_SESSION['role'] !== 'Admin') {
  header('Location: dashboard.html');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tournaments - IBT Badminton</title>
  <link rel="stylesheet" href="style.css" />
</head>

<body>
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
        <a href="booking.php" class="nav-link" id="bookingLink">Bookings</a>
        <a href="admin_reports.php" class="nav-link admin-only">Reports</a>
        <a href="admin_users.php" class="nav-link">Users</a>
        <a href="create_tourna.php" class="nav-link admin-only active">Tournaments</a>
        <a href="history.php" class="nav-link admin-only">History</a>
        <button onclick="logout()" class="btn btn-logout">Logout</button>
      </div>
    </div>
  </nav>

  <main class="main-content">
    <div class="container">
      <div class="page-header">
        <h2>🏆 Tournaments</h2>
        <p id="pageSubtitle">Manage tournaments and view participants</p>
      </div>

      <!-- Admin Actions -->
      <div style="margin-bottom: 24px">
        <button onclick="window.location.href='tourna.html'" class="btn btn-primary">
          <span>➕</span> Create New Tournament
        </button>
      </div>

      <!-- Tournaments Stats -->
      <div class="stats-grid" style="margin-bottom: 32px">
        <div class="stat-card stat-green">
          <div class="stat-content">
            <div class="stat-icon">📅</div>
            <div class="stat-value" id="upcomingCount">0</div>
          </div>
          <p class="stat-label">Upcoming Tournaments</p>
        </div>

        <div class="stat-card stat-blue">
          <div class="stat-content">
            <div class="stat-icon">🎮</div>
            <div class="stat-value" id="ongoingCount">0</div>
          </div>
          <p class="stat-label">Ongoing Tournaments</p>
        </div>

        <div class="stat-card stat-orange">
          <div class="stat-content">
            <div class="stat-icon">🏆</div>
            <div class="stat-value" id="completedCount">0</div>
          </div>
          <p class="stat-label">Completed Tournaments</p>
        </div>

        <div class="stat-card stat-purple">
          <div class="stat-content">
            <div class="stat-icon">👥</div>
            <div class="stat-value" id="totalParticipants">0</div>
          </div>
          <p class="stat-label">Total Participants</p>
        </div>
      </div>

      <!-- Tournaments List -->
      <div class="card">
        <h3 class="card-title">
          <span class="icon">🏆</span>
          All Tournaments
          <button onclick="loadTournaments()" style="
                float: right;
                background: none;
                border: none;
                cursor: pointer;
                font-size: 20px;
              " title="Refresh">
            🔄
          </button>
        </h3>
        <div id="tournamentsList">
          <div style="text-align: center; padding: 40px; color: #9ca3af">
            <div class="spinner"></div>
            <p style="margin-top: 16px">Loading tournaments...</p>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="main.js"></script>
  <script>
    const currentUser = checkAuth();

    // Verify admin access
    if (!currentUser || currentUser.role !== "Admin") {
      window.location.href = "dashboard.html";
    }

    // Load tournaments on page load
    loadTournaments();

    async function loadTournaments() {
      try {
        const response = await fetch("get_tournament.php");
        const data = await response.json();

        if (data.success) {
          displayTournaments(data.tournaments);
          updateStats(data.tournaments);
        } else {
          document.getElementById("tournamentsList").innerHTML =
            '<div style="text-align: center; padding: 40px; color: #9ca3af;">Failed to load tournaments</div>';
        }
      } catch (error) {
        console.error("Error loading tournaments:", error);
        document.getElementById("tournamentsList").innerHTML =
          '<div style="text-align: center; padding: 40px; color: #9ca3af;">Error loading tournaments</div>';
      }
    }

    function updateStats(tournaments) {
      const upcoming = tournaments.filter(
        (t) => t.status === "upcoming"
      ).length;
      const ongoing = tournaments.filter(
        (t) => t.status === "ongoing"
      ).length;
      const completed = tournaments.filter(
        (t) => t.status === "completed"
      ).length;

      // Calculate total participants across all tournaments
      const totalParticipants = tournaments.reduce((sum, t) => {
        return sum + (t.participants ? t.participants.length : 0);
      }, 0);

      document.getElementById("upcomingCount").textContent = upcoming;
      document.getElementById("ongoingCount").textContent = ongoing;
      document.getElementById("completedCount").textContent = completed;
      document.getElementById("totalParticipants").textContent =
        totalParticipants;
    }

    function displayTournaments(tournaments) {
      const container = document.getElementById("tournamentsList");

      if (!tournaments || tournaments.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #9ca3af;">
              <div style="font-size: 48px; margin-bottom: 16px;">🏆</div>
              <p>No tournaments available yet</p>
              <button onclick="window.location.href='tourna.html'" class="btn btn-primary" style="margin-top: 16px;">Create First Tournament</button>
            </div>
          `;
        return;
      }

      const statusColors = {
        upcoming: "badge-pending",
        ongoing: "badge-confirmed",
        completed: "badge-cancelled",
        cancelled: "badge-cancelled",
      };

      const statusIcons = {
        upcoming: "📅",
        ongoing: "🎮",
        completed: "🏆",
        cancelled: "❌",
      };

      container.innerHTML = tournaments
        .map((tournament) => {
          const participantCount = tournament.participants
            ? tournament.participants.length
            : 0;

          return `
              <div class="tournament-card" style="background: #f9fafb; border-radius: 12px; padding: 20px; margin-bottom: 16px; border: 2px solid #e5e7eb; transition: all 0.3s;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                  <div style="flex: 1;">
                    <h3 style="font-size: 20px; font-weight: 700; color: #111827; margin-bottom: 8px;">
                      ${statusIcons[tournament.status]} ${tournament.name}
                    </h3>
                    <span class="badge ${statusColors[tournament.status]}">${tournament.status.charAt(0).toUpperCase() +
            tournament.status.slice(1)
            }</span>
                  </div>
                  <div style="text-align: right;">
                    <div style="font-size: 24px; font-weight: 700; color: #10b981;">${participantCount}</div>
                    <div style="font-size: 12px; color: #6b7280;">Participants</div>
                  </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin: 16px 0;">
                  <div>
                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Start Date</div>
                    <div style="font-weight: 600; color: #374151;">📅 ${formatDate(
              tournament.start_date
            )}</div>
                  </div>
                  <div>
                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">End Date</div>
                    <div style="font-weight: 600; color: #374151;">📅 ${formatDate(
              tournament.end_date
            )}</div>
                  </div>
                </div>

                ${tournament.participants && tournament.participants.length > 0
              ? `
                  <div style="margin: 16px 0;">
                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">Participants:</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                      ${tournament.participants
                .slice(0, 5)
                .map(
                  (p) => `
                          <span style="background: #ecfdf5; color: #065f46; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600;">
                            👤 ${p.user_name}
                          </span>
                        `
                )
                .join("")}
                      ${tournament.participants.length > 5
                ? `<span style="background: #f3f4f6; color: #6b7280; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600;">+${tournament.participants.length - 5
                } more</span>`
                : ""
              }
                    </div>
                  </div>
                `
              : '<div style="color: #9ca3af; font-size: 14px; margin: 16px 0;">No participants yet</div>'
            }

                <div style="display: flex; gap: 8px; margin-top: 16px; flex-wrap: wrap;">
                  <button onclick="viewTournament(${tournament.tournament_id
            })" class="btn btn-sm" style="background: #3b82f6;">
                    👁️ View Details
                  </button>
                  <button onclick="editTournament(${tournament.tournament_id
            })" class="btn btn-sm">
                    ✏️ Edit
                  </button>
                  <button onclick="deleteTournament(${tournament.tournament_id
            })" class="btn btn-sm btn-danger">
                    🗑️ Delete
                  </button>
                </div>
              </div>
            `;
        })
        .join("");

      // Add hover effect
      document.querySelectorAll(".tournament-card").forEach((card) => {
        card.addEventListener("mouseenter", function () {
          this.style.borderColor = "#10b981";
          this.style.boxShadow = "0 8px 20px rgba(16, 185, 129, 0.2)";
        });
        card.addEventListener("mouseleave", function () {
          this.style.borderColor = "#e5e7eb";
          this.style.boxShadow = "none";
        });
      });
    }

    function viewTournament(tournamentId) {
      window.location.href = `tournament_details.html?id=${tournamentId}`;
    }

    function editTournament(tournamentId) {
      window.location.href = `edit_tournament.html?id=${tournamentId}`;
    }

    async function deleteTournament(tournamentId) {
      if (
        !confirm(
          "Are you sure you want to delete this tournament? This action cannot be undone."
        )
      ) {
        return;
      }

      try {
        showLoading();
        const response = await fetch("delete_tournament.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ tournament_id: tournamentId }),
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
          showNotification("Tournament deleted successfully", "success");
          loadTournaments();
        } else {
          showNotification(
            result.message || "Failed to delete tournament",
            "error"
          );
        }
      } catch (error) {
        hideLoading();
        console.error("Error:", error);
        showNotification("An error occurred", "error");
      }
    }
  </script>

  <style>
    .spinner {
      width: 24px;
      height: 24px;
      border: 3px solid rgba(16, 185, 129, 0.3);
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

    body.admin #bookingLink {
      display: none !important;
    }

    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      transition: all 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .stat-card.stat-green {
      border-left: 4px solid #10b981;
    }

    .stat-card.stat-blue {
      border-left: 4px solid #3b82f6;
    }

    .stat-card.stat-orange {
      border-left: 4px solid #f59e0b;
    }

    .stat-card.stat-purple {
      border-left: 4px solid #8b5cf6;
    }

    .stat-content {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 8px;
    }

    .stat-icon {
      font-size: 32px;
    }

    .stat-value {
      font-size: 32px;
      font-weight: 700;
      color: #111827;
    }

    .stat-label {
      font-size: 14px;
      color: #6b7280;
      margin: 0;
    }

    @media (max-width: 768px) {
      .stat-content {
        gap: 12px;
      }

      .stat-icon {
        font-size: 24px;
      }

      .stat-value {
        font-size: 24px;
      }
    }
  </style>
</body>

</html>