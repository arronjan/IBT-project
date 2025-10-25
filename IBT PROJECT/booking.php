<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Check if user is Admin - admins cannot make bookings
if ($_SESSION['role'] === 'Admin') {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Court Booking - IBT Badminton</title>
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
                <a href="booking.php" class="nav-link active">Bookings</a>
                <a href="create_tourna.php" class="nav-link">Tournaments</a>
                <a href="history.php" class="nav-link">History</a>
                <button onclick="logout()" class="btn btn-logout">Logout</button>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h2>Court Booking</h2>
                <p>Select a court and time slot for your reservation</p>
            </div>

            <div class="booking-layout">
                <div class="booking-main">
                    <div class="card">
                        <h3 class="card-title">Select Court</h3>
                        <div class="courts-grid-large" id="courtSelection">
                            <div style="text-align: center; padding: 20px; color: #9ca3af;">
                                Loading courts...
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h3 class="card-title">Select Date & Time</h3>
                        <form id="bookingForm">
                            <div class="form-group">
                                <label for="bookingDate">Date</label>
                                <input type="date" id="bookingDate" name="booking_date" required />
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="startTime">Start Time</label>
                                    <input type="time" id="startTime" name="start_time" required />
                                </div>
                                <div class="form-group">
                                    <label for="endTime">End Time</label>
                                    <input type="time" id="endTime" name="end_time" required />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="booking-sidebar">
                    <div class="card sticky">
                        <h3 class="card-title">Booking Summary</h3>
                        <div id="bookingSummary">
                            <div class="empty-state">
                                <div class="empty-icon">📅</div>
                                <p>Select a court to continue</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ========================================
         PAYMENT MODAL - COMPLETE IMPLEMENTATION
         ======================================== -->
    <div id="paymentModal" class="payment-modal" style="display: none;">
        <div class="payment-modal-content">
            <span class="payment-close" onclick="closePaymentModal()">&times;</span>

            <h2 class="payment-header">
                <span class="payment-icon">💳</span>
                Choose Payment Method
            </h2>

            <!-- STEP 1: Payment Method Selection -->
            <div id="paymentStep1">
                <div class="booking-summary-box">
                    <h3 class="summary-title">Booking Summary</h3>
                    <div class="summary-items">
                        <div class="summary-item">
                            <span class="summary-label">Court:</span>
                            <strong id="summaryCourtName">-</strong>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Date:</span>
                            <strong id="summaryDate">-</strong>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Time:</span>
                            <strong id="summaryTime">-</strong>
                        </div>
                        <div class="summary-item summary-total">
                            <span class="summary-label">Total Amount:</span>
                            <strong class="summary-amount" id="summaryAmount">₱0.00</strong>
                        </div>
                    </div>
                </div>

                <div class="payment-methods-grid">
                    <button onclick="selectPaymentMethod('walk-in')" class="payment-method-card">
                        <div class="payment-method-icon">🚶</div>
                        <div class="payment-method-title">Walk-in Payment</div>
                        <div class="payment-method-desc">Pay at the venue</div>
                    </button>

                    <button onclick="selectPaymentMethod('gcash')" class="payment-method-card">
                        <div class="payment-method-icon">💙</div>
                        <div class="payment-method-title">GCash</div>
                        <div class="payment-method-desc">Pay online now</div>
                    </button>
                </div>
            </div>

            <!-- STEP 2: GCash Payment -->
            <div id="paymentStep2" style="display: none;">
                <button onclick="backToPaymentSelection()" class="back-button">
                    <span>←</span> Back to payment methods
                </button>

                <div class="gcash-header">
                    <h3>GCash Payment Instructions</h3>
                    <p>Send payment to the GCash number below and upload proof of payment</p>
                </div>

                <div class="gcash-info-box">
                    <div class="qr-code-placeholder">
                        <div class="qr-icon">📱</div>
                        <div class="qr-text">Scan QR Code</div>
                    </div>
                    <div class="gcash-details">
                        <div class="gcash-number">GCash Number: 09XX-XXX-XXXX</div>
                        <div class="gcash-name">Account Name: IBT Badminton Center</div>
                    </div>
                    <div class="gcash-amount-box">
                        <div class="gcash-amount-label">Amount to Pay:</div>
                        <div class="gcash-amount-value" id="gcashAmount">₱0.00</div>
                    </div>
                </div>

                <div class="payment-form-group">
                    <label class="payment-label">Upload Proof of Payment *</label>
                    <input type="file" id="proofOfPayment" accept="image/*" class="payment-file-input" />
                    <span class="payment-hint">Accepted formats: JPG, PNG (Max 5MB)</span>
                </div>

                <div class="payment-form-group">
                    <label class="payment-label">GCash Reference Number *</label>
                    <input type="text" id="referenceNumber" placeholder="Enter 13-digit reference number"
                        class="payment-text-input" maxlength="20" />
                </div>

                <div class="payment-button-group">
                    <button onclick="submitGCashPayment()" class="btn btn-primary btn-full">
                        <span>✓</span> Submit Payment & Confirm Booking
                    </button>
                    <button onclick="closePaymentModal()" class="btn btn-secondary btn-full">
                        Cancel
                    </button>
                </div>
            </div>

            <!-- STEP 3: Walk-in Confirmation -->
            <div id="paymentStep3" style="display: none;">
                <div class="walkin-confirmation">
                    <div class="walkin-icon">🚶</div>
                    <h3>Walk-in Payment Selected</h3>
                    <p>You will pay at the venue when you arrive</p>
                </div>

                <div class="walkin-reminder">
                    <div class="reminder-icon">⚠️</div>
                    <div class="reminder-content">
                        <div class="reminder-title">Important Reminder</div>
                        <div class="reminder-text">
                            Please arrive 15 minutes before your scheduled time.
                            Bring exact payment amount to speed up the process.
                        </div>
                    </div>
                </div>

                <div class="payment-button-group">
                    <button onclick="confirmWalkInPayment()" class="btn btn-primary btn-full">
                        <span>✓</span> Confirm Booking
                    </button>
                    <button onclick="backToPaymentSelection()" class="btn btn-secondary btn-full">
                        Back
                    </button>
                </div>
            </div>

            <div id="paymentMessage" class="payment-message" style="display: none;"></div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="main.js"></script>
    <script src="booking.js"></script>

    <script>
        // ========================================
        // PAYMENT MODAL FUNCTIONS
        // ========================================
        let currentBookingData = {};

        function openPaymentModal(bookingData) {
            currentBookingData = bookingData;

            // Populate summary
            document.getElementById("summaryCourtName").textContent = bookingData.courtName;
            document.getElementById("summaryDate").textContent = formatDate(bookingData.date);
            document.getElementById("summaryTime").textContent =
                formatTime(bookingData.startTime) + " - " + formatTime(bookingData.endTime);
            document.getElementById("summaryAmount").textContent =
                "₱" + parseFloat(bookingData.amount).toFixed(2);
            document.getElementById("gcashAmount").textContent =
                "₱" + parseFloat(bookingData.amount).toFixed(2);

            // Show modal and reset to step 1
            document.getElementById("paymentModal").style.display = "flex";
            document.getElementById("paymentStep1").style.display = "block";
            document.getElementById("paymentStep2").style.display = "none";
            document.getElementById("paymentStep3").style.display = "none";
            document.getElementById("paymentMessage").style.display = "none";

            // Reset form fields
            document.getElementById("proofOfPayment").value = "";
            document.getElementById("referenceNumber").value = "";
        }

        function closePaymentModal() {
            document.getElementById("paymentModal").style.display = "none";
            document.getElementById("proofOfPayment").value = "";
            document.getElementById("referenceNumber").value = "";
            document.getElementById("paymentMessage").style.display = "none";
        }

        function selectPaymentMethod(method) {
            if (method === "gcash") {
                document.getElementById("paymentStep1").style.display = "none";
                document.getElementById("paymentStep2").style.display = "block";
                document.getElementById("paymentStep3").style.display = "none";
            } else if (method === "walk-in") {
                document.getElementById("paymentStep1").style.display = "none";
                document.getElementById("paymentStep2").style.display = "none";
                document.getElementById("paymentStep3").style.display = "block";
            }
        }

        function backToPaymentSelection() {
            document.getElementById("paymentStep1").style.display = "block";
            document.getElementById("paymentStep2").style.display = "none";
            document.getElementById("paymentStep3").style.display = "none";
            document.getElementById("paymentMessage").style.display = "none";
        }

        async function submitGCashPayment() {
            const proofFile = document.getElementById("proofOfPayment").files[0];
            const refNumber = document.getElementById("referenceNumber").value.trim();

            // Validation
            if (!proofFile) {
                showPaymentMessage("Please upload proof of payment", "error");
                return;
            }

            if (!refNumber || refNumber.length < 10) {
                showPaymentMessage("Please enter a valid reference number (minimum 10 characters)", "error");
                return;
            }

            if (proofFile.size > 5 * 1024 * 1024) {
                showPaymentMessage("File size must be less than 5MB", "error");
                return;
            }

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!allowedTypes.includes(proofFile.type)) {
                showPaymentMessage("Only JPG and PNG images are allowed", "error");
                return;
            }

            try {
                showLoading();
                const base64Image = await fileToBase64(proofFile);

                const bookingData = {
                    user_id: currentBookingData.user_id,
                    court_id: currentBookingData.court_id,
                    booking_date: currentBookingData.date,
                    start_time: currentBookingData.startTime,
                    end_time: currentBookingData.endTime,
                    payment_method: "GCash",
                    gcash_reference: refNumber,
                    proof_of_payment: base64Image
                };

                console.log('Submitting GCash booking:', {
                    ...bookingData,
                    proof_of_payment: 'base64_image_data'
                });

                const response = await fetch("create_booking.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(bookingData)
                });

                const result = await response.json();
                console.log('GCash booking response:', result);

                hideLoading();

                if (result.success) {
                    showNotification("✓ Booking created! Payment is pending verification.", "success");
                    setTimeout(() => {
                        closePaymentModal();
                        window.location.href = "dashboard.php";
                    }, 2000);
                } else {
                    showPaymentMessage(result.message || "Failed to create booking", "error");
                }
            } catch (error) {
                hideLoading();
                console.error("GCash payment error:", error);
                showPaymentMessage("An error occurred. Please try again.", "error");
            }
        }

        async function confirmWalkInPayment() {
            try {
                showLoading();

                const bookingData = {
                    user_id: currentBookingData.user_id,
                    court_id: currentBookingData.court_id,
                    booking_date: currentBookingData.date,
                    start_time: currentBookingData.startTime,
                    end_time: currentBookingData.endTime,
                    payment_method: "Walk-in"
                };

                console.log('Submitting Walk-in booking:', bookingData);

                const response = await fetch("create_booking.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(bookingData)
                });

                const result = await response.json();
                console.log('Walk-in booking response:', result);

                hideLoading();

                if (result.success) {
                    showNotification("✓ Booking confirmed! Please pay at the venue.", "success");
                    setTimeout(() => {
                        closePaymentModal();
                        window.location.href = "dashboard.php";
                    }, 2000);
                } else {
                    showPaymentMessage(result.message || "Failed to create booking", "error");
                }
            } catch (error) {
                hideLoading();
                console.error("Walk-in payment error:", error);
                showPaymentMessage("An error occurred. Please try again.", "error");
            }
        }

        function fileToBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        }

        function showPaymentMessage(message, type) {
            const messageDiv = document.getElementById("paymentMessage");
            messageDiv.textContent = message;
            messageDiv.style.display = "block";
            messageDiv.className = "payment-message " + (type === "success" ? "payment-success" : "payment-error");

            setTimeout(() => {
                messageDiv.style.display = "none";
            }, 5000);
        }

        // Close modal when clicking outside
        window.addEventListener('click', function (event) {
            const modal = document.getElementById("paymentModal");
            if (event.target === modal) {
                closePaymentModal();
            }
        });

        // Prevent modal content clicks from closing modal
        document.addEventListener('DOMContentLoaded', function () {
            const modalContent = document.querySelector('.payment-modal-content');
            if (modalContent) {
                modalContent.addEventListener('click', function (event) {
                    event.stopPropagation();
                });
            }
        });
    </script>

    <style>
        /* ========================================
       PAYMENT MODAL STYLES
       ======================================== */
        .payment-modal {
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-y: auto;
            backdrop-filter: blur(3px);
        }

        .payment-modal-content {
            background-color: white;
            padding: 32px;
            border-radius: 16px;
            width: 100%;
            max-width: 600px;
            position: relative;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .payment-close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 32px;
            font-weight: bold;
            color: #9ca3af;
            cursor: pointer;
            line-height: 1;
            transition: color 0.3s;
            z-index: 1;
        }

        .payment-close:hover {
            color: #111827;
        }

        .payment-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }

        .payment-icon {
            font-size: 32px;
        }

        /* Booking Summary Box */
        .booking-summary-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            border: 2px solid #e5e7eb;
        }

        .summary-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 16px;
        }

        .summary-items {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .summary-label {
            color: #6b7280;
            font-size: 14px;
        }

        .summary-total {
            padding-top: 12px;
            border-top: 2px solid #e5e7eb;
            font-size: 18px;
            margin-top: 4px;
        }

        .summary-amount {
            color: #10b981;
            font-size: 20px;
            font-weight: 800;
        }

        /* Payment Methods Grid */
        .payment-methods-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 24px;
        }

        .payment-method-card {
            padding: 24px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .payment-method-card:hover {
            border-color: #10b981;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.2);
            transform: translateY(-4px);
        }

        .payment-method-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .payment-method-title {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 8px;
            color: #111827;
        }

        .payment-method-desc {
            font-size: 14px;
            color: #6b7280;
        }

        /* Back Button */
        .back-button {
            background: none;
            border: none;
            color: #10b981;
            font-weight: 600;
            margin-bottom: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            padding: 8px 0;
            transition: color 0.3s;
        }

        .back-button:hover {
            color: #059669;
        }

        /* GCash Section */
        .gcash-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .gcash-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .gcash-header p {
            color: #6b7280;
            font-size: 14px;
        }

        .gcash-info-box {
            background: linear-gradient(135deg, #007dff, #0062cc);
            padding: 24px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 24px;
        }

        .qr-code-placeholder {
            background: white;
            padding: 20px;
            border-radius: 12px;
            display: inline-block;
            margin-bottom: 16px;
            width: 200px;
            height: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px dashed #d1d5db;
        }

        .qr-icon {
            font-size: 48px;
            margin-bottom: 8px;
        }

        .qr-text {
            font-size: 14px;
            color: #6b7280;
            font-weight: 600;
        }

        .gcash-details {
            margin-bottom: 16px;
        }

        .gcash-number {
            color: white;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .gcash-name {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }

        .gcash-amount-box {
            margin-top: 16px;
            padding: 16px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .gcash-amount-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin-bottom: 4px;
        }

        .gcash-amount-value {
            color: white;
            font-size: 28px;
            font-weight: 800;
        }

        /* Form Groups */
        .payment-form-group {
            margin-bottom: 24px;
        }

        .payment-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .payment-file-input,
        .payment-text-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            box-sizing: border-box;
        }

        .payment-file-input {
            cursor: pointer;
        }

        .payment-text-input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .payment-hint {
            font-size: 12px;
            color: #6b7280;
            display: block;
            margin-top: 8px;
        }

        /* Walk-in Section */
        .walkin-confirmation {
            text-align: center;
            margin-bottom: 24px;
        }

        .walkin-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .walkin-confirmation h3 {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .walkin-confirmation p {
            color: #6b7280;
            font-size: 16px;
        }

        .walkin-reminder {
            background: #fef3c7;
            padding: 16px;
            border-radius: 12px;
            border: 2px solid #fbbf24;
            margin-bottom: 24px;
            display: flex;
            gap: 12px;
        }

        .reminder-icon {
            font-size: 24px;
            flex-shrink: 0;
        }

        .reminder-title {
            font-weight: 700;
            color: #92400e;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .reminder-text {
            font-size: 14px;
            color: #92400e;
            line-height: 1.5;
        }

        /* Button Group */
        .payment-button-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-full {
            width: 100%;
        }

        /* Payment Message */
        .payment-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-top: 16px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
        }

        .payment-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .payment-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .payment-modal-content {
                padding: 24px;
                max-height: 95vh;
            }

            .payment-methods-grid {
                grid-template-columns: 1fr;
            }

            .payment-method-card {
                padding: 20px;
            }

            .payment-method-icon {
                font-size: 40px;
            }

            .qr-code-placeholder {
                width: 180px;
                height: 180px;
            }

            .gcash-amount-value {
                font-size: 24px;
            }

            .walkin-icon {
                font-size: 48px;
            }
        }
    </style>
</body>

</html>