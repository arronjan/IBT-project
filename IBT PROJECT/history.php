<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: index.html');
  exit;
}

// Get user data from session
$userName = $_SESSION['name'];
$userRole = $_SESSION['role'];
$userId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>History - IBT Badminton</title>
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
        <a href="create_tourna.php"
          class="nav-link <?php echo $userRole === 'Admin' ? '' : 'admin-only'; ?>">Tournaments</a>
        <a href="history.php" class="nav-link active">History</a>
        <button onclick="logout()" class="btn btn-logout">Logout</button>
      </div>
    </div>
  </nav>

  <main class="main-content">
    <div class="container">
      <div class="page-header">
        <h2>📜 History</h2>
        <p id="pageSubtitle">
          <?php
          if ($userRole === 'Admin') {
            echo 'View all bookings, tournaments, and payment history';
          } else {
            echo 'View your booking and tournament history';
          }
          ?>
        </p>
      </div>

      <!-- Statistics Cards -->
      <div class="stats-grid" style="margin-bottom: 32px">
        <div class="stat-card stat-green">
          <div class="stat-content">
            <div class="stat-icon">📅</div>
            <div class="stat-value" id="totalBookings">
              <div class="spinner"></div>
            </div>
          </div>
          <p class="stat-label">Total Bookings</p>
        </div>

        <div class="stat-card stat-blue">
          <div class="stat-content">
            <div class="stat-icon">✅</div>
            <div class="stat-value" id="completedBookings">
              <div class="spinner"></div>
            </div>
          </div>
          <p class="stat-label">Completed</p>
        </div>

        <div class="stat-card stat-purple">
          <div class="stat-content">
            <div class="stat-icon">🏆</div>
            <div class="stat-value" id="tournamentsJoined">
              <div class="spinner"></div>
            </div>
          </div>
          <p class="stat-label">Tournaments Joined</p>
        </div>

        <div class="stat-card stat-orange">
          <div class="stat-content">
            <div class="stat-icon">💰</div>
            <div class="stat-value" id="totalSpent">
              <div class="spinner"></div>
            </div>
          </div>
          <p class="stat-label" id="spentLabel">
            <?php echo $userRole === 'Admin' ? 'Total Revenue' : 'Total Spent'; ?>
          </p>
        </div>
      </div>

      <!-- Filter Tabs -->
      <div class="card" style="margin-bottom: 24px">
        <div style="display: flex; gap: 12px; flex-wrap: wrap">
          <button class="filter-btn active" data-filter="all" onclick="filterHistory('all')">
            📋 All History
          </button>
          <button class="filter-btn" data-filter="bookings" onclick="filterHistory('bookings')">
            🏸 Bookings
          </button>
          <button class="filter-btn" data-filter="tournaments" onclick="filterHistory('tournaments')">
            🏆 Tournaments
          </button>
          <button class="filter-btn" data-filter="payments" onclick="filterHistory('payments')">
            💳 Payments
          </button>
        </div>
      </div>

      <!-- Bookings History Section -->
      <div id="bookingsSection" class="card history-section">
        <h3 class="card-title">
          <span class="icon">🏸</span>
          <span id="bookingsTitle">
            <?php echo $userRole === 'Admin' ? 'All Bookings History' : 'Booking History'; ?>
          </span>
          <button onclick="loadHistory()" style="
                float: right;
                background: none;
                border: none;
                cursor: pointer;
                font-size: 20px;
              " title="Refresh">
            🔄
          </button>
        </h3>

        <!-- Date Filter -->
        <div style="
              margin-bottom: 20px;
              display: flex;
              gap: 12px;
              flex-wrap: wrap;
              align-items: center;
            ">
          <input type="date" id="startDate" style="
                padding: 8px 12px;
                border: 2px solid #e5e7eb;
                border-radius: 8px;
              " />
          <span style="color: #6b7280">to</span>
          <input type="date" id="endDate" style="
                padding: 8px 12px;
                border: 2px solid #e5e7eb;
                border-radius: 8px;
              " />
          <button onclick="applyDateFilter()" class="btn btn-sm" style="background: #10b981">
            Apply
          </button>
          <button onclick="clearDateFilter()" class="btn btn-sm" style="background: #6b7280">
            Clear
          </button>
        </div>

        <div id="bookingsList">
          <div style="text-align: center; padding: 40px; color: #9ca3af">
            <div class="spinner"></div>
            <p style="margin-top: 16px">Loading booking history...</p>
          </div>
        </div>
      </div>

      <!-- Tournaments History Section -->
      <div id="tournamentsSection" class="card history-section" style="margin-top: 24px">
        <h3 class="card-title">
          <span class="icon">🏆</span>
          Tournament History
        </h3>
        <div id="tournamentsList">
          <div style="text-align: center; padding: 40px; color: #9ca3af">
            <div class="spinner"></div>
            <p style="margin-top: 16px">Loading tournament history...</p>
          </div>
        </div>
      </div>

      <!-- Payments History Section -->
      <div id="paymentsSection" class="card history-section" style="margin-top: 24px">
        <h3 class="card-title">
          <span class="icon">💳</span>
          Payment History
        </h3>
        <div id="paymentsList">
          <div style="text-align: center; padding: 40px; color: #9ca3af">
            <div class="spinner"></div>
            <p style="margin-top: 16px">Loading payment history...</p>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="main.js"></script>
  <script>
    const currentUser = checkAuth();
    let allBookings = [];
    let filteredBookings = [];

    // Load all history data
    loadHistory();

    async function loadHistory() {
      try {
        await Promise.all([
          loadBookingsHistory(),
          loadTournamentsHistory(),
          loadPaymentsHistory(),
          loadStats(),
        ]);
      } catch (error) {
        console.error("Error loading history:", error);
        showNotification("Error loading history data", "error");
      }
    }

    async function loadStats() {
      try {
        const user = JSON.parse(sessionStorage.getItem("user"));
        const url = `get_history_stats.php?user_id=${user.user_id}&role=${user.role}`;

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
          document.getElementById("totalBookings").textContent =
            data.stats.total_bookings || 0;
          document.getElementById("completedBookings").textContent =
            data.stats.completed_bookings || 0;
          document.getElementById("tournamentsJoined").textContent =
            data.stats.tournaments_joined || 0;
          document.getElementById("totalSpent").textContent =
            "₱" + (data.stats.total_spent || 0).toLocaleString();
        }
      } catch (error) {
        console.error("Error loading stats:", error);
        // Set default values on error
        document.getElementById("totalBookings").textContent = "0";
        document.getElementById("completedBookings").textContent = "0";
        document.getElementById("tournamentsJoined").textContent = "0";
        document.getElementById("totalSpent").textContent = "₱0";
      }
    }

    async function loadBookingsHistory() {
      try {
        const user = JSON.parse(sessionStorage.getItem("user"));
        const url =
          user.role === "Admin"
            ? "get_bookings.php?all=true"
            : `get_bookings.php?user_id=${user.user_id}`;

        const response = await fetch(url);
        const data = await response.json();

        allBookings = Array.isArray(data) ? data : [];
        filteredBookings = allBookings;
        displayBookings(filteredBookings);
      } catch (error) {
        console.error("Error loading bookings:", error);
        document.getElementById("bookingsList").innerHTML =
          '<div style="text-align: center; padding: 40px; color: #9ca3af;">Error loading bookings</div>';
      }
    }

    function displayBookings(bookings) {
      const container = document.getElementById("bookingsList");

      if (!bookings || bookings.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #9ca3af;">
              <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
              <p>No booking history found</p>
            </div>
          `;
        return;
      }

      // Sort by date (most recent first)
      bookings.sort(
        (a, b) => new Date(b.booking_date) - new Date(a.booking_date)
      );

      container.innerHTML = `
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Court</th>
                  ${currentUser.role === "Admin" ? "<th>Player</th>" : ""}
                  <th>Time</th>
                  <th>Duration</th>
                  <th>Amount</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                ${bookings
          .map(
            (booking) => `
                    <tr>
                      <td>${formatDate(booking.booking_date)}</td>
                      <td><strong>${booking.court_name}</strong></td>
                      ${currentUser.role === "Admin"
                ? `<td>${booking.user_name}</td>`
                : ""
              }
                      <td>${formatTime(booking.start_time)} - ${formatTime(
                booking.end_time
              )}</td>
                      <td>${calculateDuration(
                booking.start_time,
                booking.end_time
              )}</td>
                      <td><strong style="color: #10b981;">₱${parseFloat(
                booking.total_amount || 0
              ).toFixed(2)}</strong></td>
                      <td><span class="badge badge-${booking.status.toLowerCase()}">${booking.status
              }</span></td>
                    </tr>
                  `
          )
          .join("")}
              </tbody>
            </table>
          </div>
        `;
    }

    async function loadTournamentsHistory() {
      try {
        const response = await fetch("get_tournament.php");
        const data = await response.json();

        if (data.success) {
          const tournaments = data.tournaments;

          // Filter tournaments based on role
          let filteredTournaments;
          if (currentUser.role === "Admin") {
            filteredTournaments = tournaments;
          } else {
            // Show only tournaments user joined
            filteredTournaments = tournaments.filter(
              (t) =>
                t.participants &&
                t.participants.some((p) => parseInt(p.player_id) === parseInt(currentUser.user_id))
            );
          }

          displayTournaments(filteredTournaments);
        }
      } catch (error) {
        console.error("Error loading tournaments:", error);
        document.getElementById("tournamentsList").innerHTML =
          '<div style="text-align: center; padding: 40px; color: #9ca3af;">Error loading tournaments</div>';
      }
    }

    function displayTournaments(tournaments) {
      const container = document.getElementById("tournamentsList");

      if (!tournaments || tournaments.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #9ca3af;">
              <div style="font-size: 48px; margin-bottom: 16px;">🏆</div>
              <p>No tournament history found</p>
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

      container.innerHTML = `
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Tournament Name</th>
                  <th>Start Date</th>
                  <th>End Date</th>
                  <th>Participants</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                ${tournaments
          .map(
            (tournament) => `
                    <tr>
                      <td><strong>${statusIcons[tournament.status] || "🏆"} ${tournament.name
              }</strong></td>
                      <td>${formatDate(tournament.start_date)}</td>
                      <td>${formatDate(tournament.end_date)}</td>
                      <td>${tournament.participants
                ? tournament.participants.length
                : 0
              } players</td>
                      <td><span class="badge ${statusColors[tournament.status]
              }">${tournament.status.charAt(0).toUpperCase() +
              tournament.status.slice(1)
              }</span></td>
                    </tr>
                  `
          )
          .join("")}
              </tbody>
            </table>
          </div>
        `;
    }

    async function loadPaymentsHistory() {
      try {
        const user = JSON.parse(sessionStorage.getItem("user"));
        const url =
          user.role === "Admin"
            ? "get_payments.php"
            : `get_payments.php?user_id=${user.user_id}`;

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
          displayPayments(data.payments || []);
        } else {
          document.getElementById("paymentsList").innerHTML =
            '<div style="text-align: center; padding: 40px; color: #9ca3af;">No payment history</div>';
        }
      } catch (error) {
        console.error("Error loading payments:", error);
        document.getElementById("paymentsList").innerHTML =
          '<div style="text-align: center; padding: 40px; color: #9ca3af;">Error loading payments</div>';
      }
    }

    function displayPayments(payments) {
      const container = document.getElementById("paymentsList");

      if (!payments || payments.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #9ca3af;">
              <div style="font-size: 48px; margin-bottom: 16px;">💳</div>
              <p>No payment history found</p>
            </div>
          `;
        return;
      }

      const statusColors = {
        completed: "badge-confirmed",
        pending: "badge-pending",
        failed: "badge-cancelled",
      };

      container.innerHTML = `
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Booking ID</th>
                  ${currentUser.role === "Admin" ? "<th>Player</th>" : ""}
                  <th>Amount</th>
                  <th>Method</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                ${payments
          .map(
            (payment) => `
                    <tr>
                      <td>${formatDate(payment.payment_date)}</td>
                      <td>#${payment.booking_id}</td>
                      ${currentUser.role === "Admin"
                ? `<td>${payment.user_name || "N/A"}</td>`
                : ""
              }
                      <td><strong style="color: #10b981;">₱${parseFloat(
                payment.amount
              ).toFixed(2)}</strong></td>
                      <td>${payment.payment_method}</td>
                      <td><span class="badge ${statusColors[payment.status]
              }">${payment.status}</span></td>
                    </tr>
                  `
          )
          .join("")}
              </tbody>
            </table>
          </div>
        `;
    }

    function filterHistory(filter) {
      // Update active button
      document.querySelectorAll(".filter-btn").forEach((btn) => {
        btn.classList.remove("active");
      });
      event.target.classList.add("active");

      // Show/hide sections
      const sections = [
        "bookingsSection",
        "tournamentsSection",
        "paymentsSection",
      ];

      if (filter === "all") {
        sections.forEach(
          (id) => (document.getElementById(id).style.display = "block")
        );
      } else if (filter === "bookings") {
        document.getElementById("bookingsSection").style.display = "block";
        document.getElementById("tournamentsSection").style.display = "none";
        document.getElementById("paymentsSection").style.display = "none";
      } else if (filter === "tournaments") {
        document.getElementById("bookingsSection").style.display = "none";
        document.getElementById("tournamentsSection").style.display = "block";
        document.getElementById("paymentsSection").style.display = "none";
      } else if (filter === "payments") {
        document.getElementById("bookingsSection").style.display = "none";
        document.getElementById("tournamentsSection").style.display = "none";
        document.getElementById("paymentsSection").style.display = "block";
      }
    }

    function applyDateFilter() {
      const startDate = document.getElementById("startDate").value;
      const endDate = document.getElementById("endDate").value;

      if (!startDate || !endDate) {
        showNotification("Please select both start and end dates", "error");
        return;
      }

      if (new Date(startDate) > new Date(endDate)) {
        showNotification("Start date must be before end date", "error");
        return;
      }

      filteredBookings = allBookings.filter((booking) => {
        const bookingDate = new Date(booking.booking_date);
        return (
          bookingDate >= new Date(startDate) &&
          bookingDate <= new Date(endDate)
        );
      });

      displayBookings(filteredBookings);
      showNotification(
        `Showing ${filteredBookings.length} booking(s) from ${formatDate(
          startDate
        )} to ${formatDate(endDate)}`,
        "success"
      );
    }

    function clearDateFilter() {
      document.getElementById("startDate").value = "";
      document.getElementById("endDate").value = "";
      filteredBookings = allBookings;
      displayBookings(filteredBookings);
      showNotification("Date filter cleared", "success");
    }

    function calculateDuration(startTime, endTime) {
      const start = new Date(`1970-01-01 ${startTime}`);
      const end = new Date(`1970-01-01 ${endTime}`);
      const diff = (end - start) / (1000 * 60 * 60);
      return `${diff} hr${diff !== 1 ? "s" : ""}`;
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

    .filter-btn {
      padding: 10px 20px;
      border: 2px solid #e5e7eb;
      background: white;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }

    .filter-btn:hover {
      border-color: #10b981;
      background: #ecfdf5;
    }

    .filter-btn.active {
      background: #10b981;
      color: white;
      border-color: #10b981;
    }

    .history-section {
      margin-bottom: 24px;
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

    .stat-card.stat-purple {
      border-left: 4px solid #8b5cf6;
    }

    .stat-card.stat-orange {
      border-left: 4px solid #f59e0b;
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
      min-height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .stat-label {
      font-size: 14px;
      color: #6b7280;
      margin: 0;
    }

    body.admin #bookingLink {
      display: none !important;
    }

    @media (max-width: 768px) {
      .filter-btn {
        font-size: 14px;
        padding: 8px 16px;
      }

      .stat-content {
        gap: 12px;
      }

      .stat-icon {
        font-size: 24px;
      }

      .stat-value {
        font-size: 24px;
      }

      .table {
        font-size: 14px;
      }
    }
  </style>
</body>

</html>