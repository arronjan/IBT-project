<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

// Check if tournament ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  header('Location: create_tourna.php');
  exit;
}

$tournamentId = $_GET['id'];
$userRole = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tournament Details - IBT Badminton</title>
  <link rel="stylesheet" href="style.css" />
</head>

<body class="<?php echo strtolower($userRole); ?>">
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
        <a href="admin_reports.php"
          class="nav-link <?php echo $userRole === 'Admin' ? '' : 'admin-only'; ?>">Reports</a>
        <a href="admin_users.php" class="nav-link <?php echo $userRole === 'Admin' ? '' : 'admin-only'; ?>">Users</a>
        <a href="create_tourna.php" class="nav-link active">Tournaments</a>
        <a href="history.php" class="nav-link">History</a>
        <button onclick="logout()" class="btn btn-logout">Logout</button>
      </div>
    </div>
  </nav>

  <main class="main-content">
    <div class="container">
      <div class="page-header">
        <button onclick="window.location.href='create_tourna.php'" class="back-btn">
          ← Back to Tournaments
        </button>
        <h2 id="tournamentTitle">🏆 Tournament Details</h2>
        <p id="tournamentSubtitle">Loading...</p>
      </div>

      <div id="loadingState" style="text-align: center; padding: 60px">
        <div class="spinner"></div>
        <p style="margin-top: 20px; color: #6b7280">
          Loading tournament details...
        </p>
      </div>

      <div id="tournamentContent" style="display: none">
        <!-- Tournament Info Card -->
        <div class="card" style="margin-bottom: 24px">
          <div class="card-title">
            <span class="icon">📋</span>
            Tournament Information
            <?php if ($userRole === 'Admin'): ?>
              <button
                onclick="window.location.href='edit_tournament.php?id=<?php echo htmlspecialchars($tournamentId); ?>'"
                class="btn btn-sm" style="float: right; background: #10b981;">
                ✏️ Edit Tournament
              </button>
            <?php endif; ?>
          </div>
          <div id="tournamentInfo"></div>
        </div>

        <!-- Participants List -->
        <div class="card">
          <div class="card-title">
            <span class="icon">👥</span>
            Participants (<span id="participantCount">0</span>)
          </div>
          <div id="participantsList"></div>
        </div>

        <!-- Admin Actions (if admin) -->
        <?php if ($userRole === 'Admin'): ?>
          <div class="card" style="margin-top: 24px; background: #f9fafb;">
            <div class="card-title">
              <span class="icon">⚙️</span>
              Admin Actions
            </div>
            <div style="padding: 20px; display: flex; gap: 12px; flex-wrap: wrap;">
              <button
                onclick="window.location.href='edit_tournament.php?id=<?php echo htmlspecialchars($tournamentId); ?>'"
                class="btn" style="background: #10b981;">
                ✏️ Edit Tournament
              </button>
              <button onclick="deleteTournament(<?php echo htmlspecialchars($tournamentId); ?>)" class="btn btn-danger">
                🗑️ Delete Tournament
              </button>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <script src="main.js"></script>
  <script>
    const currentUser = checkAuth();
    const tournamentId = <?php echo json_encode($tournamentId); ?>;

    loadTournamentDetails();

    async function loadTournamentDetails() {
      try {
        showLoading();
        const response = await fetch(
          `get_tournament_by_id.php?id=${tournamentId}`
        );
        const data = await response.json();
        hideLoading();

        if (data.success) {
          displayTournamentDetails(data.tournament);
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

    function displayTournamentDetails(tournament) {
      // Update page title
      document.getElementById(
        "tournamentTitle"
      ).textContent = `🏆 ${tournament.name}`;

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

      document.getElementById("tournamentSubtitle").innerHTML = `
          <span class="badge ${statusColors[tournament.status]}">${statusIcons[tournament.status]
        } ${tournament.status.charAt(0).toUpperCase() + tournament.status.slice(1)
        }</span>
        `;

      // Display tournament info
      document.getElementById("tournamentInfo").innerHTML = `
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-top: 20px;">
            <div class="info-box">
              <div class="info-label">Start Date</div>
              <div class="info-value">📅 ${formatDate(tournament.start_date)}</div>
            </div>
            <div class="info-box">
              <div class="info-label">End Date</div>
              <div class="info-value">📅 ${formatDate(tournament.end_date)}</div>
            </div>
            <div class="info-box">
              <div class="info-label">Duration</div>
              <div class="info-value">⏱️ ${calculateDuration(
        tournament.start_date,
        tournament.end_date
      )}</div>
            </div>
            <div class="info-box">
              <div class="info-label">Total Participants</div>
              <div class="info-value">👥 ${tournament.participants ? tournament.participants.length : 0}</div>
            </div>
          </div>
        `;

      // Display participants
      const participants = tournament.participants || [];
      document.getElementById("participantCount").textContent =
        participants.length;

      if (participants.length === 0) {
        document.getElementById("participantsList").innerHTML = `
            <div style="text-align: center; padding: 40px; color: #9ca3af;">
              <div style="font-size: 48px; margin-bottom: 16px;">👥</div>
              <p>No participants have joined this tournament yet</p>
              ${tournament.status === 'upcoming' ? '<p style="margin-top: 8px; font-size: 14px;">Members can join from the tournaments page</p>' : ''}
            </div>
          `;
      } else {
        document.getElementById("participantsList").innerHTML = `
            <div class="table-responsive" style="margin-top: 20px;">
              <table class="table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Player Name</th>
                    <th>Player ID</th>
                    <th>Joined Date</th>
                  </tr>
                </thead>
                <tbody>
                  ${participants
            .map(
              (p, index) => `
                      <tr>
                        <td>${index + 1}</td>
                        <td><strong>👤 ${p.user_name || "Unknown Player"}</strong></td>
                        <td>#${p.player_id}</td>
                        <td>${p.joined_date ? formatDate(p.joined_date) : 'N/A'}</td>
                      </tr>
                    `
            )
            .join("")}
                </tbody>
              </table>
            </div>
          `;
      }

      // Hide loading, show content
      document.getElementById("loadingState").style.display = "none";
      document.getElementById("tournamentContent").style.display = "block";
    }

    async function deleteTournament(tournamentId) {
      if (!confirm("Are you sure you want to delete this tournament? This action cannot be undone.")) {
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
          setTimeout(() => {
            window.location.href = "create_tourna.php";
          }, 1500);
        } else {
          showNotification(result.message || "Failed to delete tournament", "error");
        }
      } catch (error) {
        hideLoading();
        console.error("Error:", error);
        showNotification("An error occurred", "error");
      }
    }

    function calculateDuration(startDate, endDate) {
      const start = new Date(startDate + 'T00:00:00');
      const end = new Date(endDate + 'T00:00:00');
      const diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
      return diffDays === 1 ? "1 day" : `${diffDays} days`;
    }

    function formatDate(dateString) {
      const date = new Date(dateString + 'T00:00:00');
      return date.toLocaleDateString("en-US", {
        month: "long",
        day: "numeric",
        year: "numeric",
      });
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

    .back-btn {
      background: none;
      border: none;
      color: #10b981;
      font-weight: 600;
      cursor: pointer;
      font-size: 16px;
      margin-bottom: 10px;
      padding: 8px 0;
      transition: color 0.3s;
    }

    .back-btn:hover {
      color: #059669;
    }

    .info-box {
      padding: 16px;
      background: #f9fafb;
      border-radius: 8px;
      border-left: 4px solid #10b981;
    }

    .info-label {
      font-size: 14px;
      color: #6b7280;
      margin-bottom: 8px;
    }

    .info-value {
      font-size: 18px;
      font-weight: 700;
      color: #111827;
    }

    body.admin #bookingLink {
      display: none !important;
    }

    @media (max-width: 768px) {
      .info-box {
        padding: 12px;
      }

      .info-value {
        font-size: 16px;
      }
    }
  </style>
</body>

</html>