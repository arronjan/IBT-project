window.submitBooking = async function() {
    console.log('=== SUBMIT BOOKING CALLED ===');
    
    const user = JSON.parse(sessionStorage.getItem('user'));
    if (!user) {
        showNotification('Please login to continue', 'error');
        return;
    }
    
    if (!selectedCourt) {
        showNotification('Please select a court', 'error');
        return;
    }
    
    const bookingDate = document.getElementById('bookingDate').value;
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;
    
    if (!bookingDate || !startTime || !endTime) {
        showNotification('Please fill in all booking details', 'error');
        return;
    }
    
    const bookingData = {
        user_id: user.user_id,
        court_id: selectedCourt.court_id,
        booking_date: bookingDate,
        start_time: startTime,
        end_time: endTime
    };
    
    const validation = validateBookingForm(bookingData);
    if (!validation.valid) {
        showNotification(validation.errors[0], 'error');
        return;
    }
    
    const bookingDateObj = new Date(bookingData.booking_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (bookingDateObj < today) {
        showNotification('Cannot book for past dates', 'error');
        return;
    }
    
    const price = calculatePrice(startTime, endTime);
    
    // FIXED: Open payment modal instead of directly creating booking
    openPaymentModal({
        user_id: user.user_id,
        court_id: selectedCourt.court_id,
        courtName: selectedCourt.court_name,
        date: bookingDate,
        startTime: startTime,
        endTime: endTime,
        amount: price
    });
}