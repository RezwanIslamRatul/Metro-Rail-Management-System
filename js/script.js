/**
 * Metro Rail Booking System - Custom JavaScript
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Form validation
    var forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });

    // Alert auto-dismiss
    var alertList = document.querySelectorAll('.alert-dismissible');
    
    alertList.forEach(function(alert) {
        setTimeout(function() {
            var closeButton = alert.querySelector('.btn-close');
            if (closeButton) {
                closeButton.click();
            }
        }, 5000);
    });

    // Station selection validation
    var fromStation = document.getElementById('from-station');
    var toStation = document.getElementById('to-station');
    
    if (fromStation && toStation) {
        var validateStations = function() {
            if (fromStation.value && toStation.value && fromStation.value === toStation.value) {
                toStation.setCustomValidity('Departure and arrival stations cannot be the same');
            } else {
                toStation.setCustomValidity('');
            }
        };
        
        fromStation.addEventListener('change', validateStations);
        toStation.addEventListener('change', validateStations);
    }

    // Date validation
    var journeyDate = document.getElementById('journey-date');
    
    if (journeyDate) {
        var today = new Date().toISOString().split('T')[0];
        journeyDate.setAttribute('min', today);
        
        var maxDate = new Date();
        maxDate.setDate(maxDate.getDate() + 30);
        var maxDateStr = maxDate.toISOString().split('T')[0];
        journeyDate.setAttribute('max', maxDateStr);
    }

    // Fare calculator
    var calculateFareBtn = document.getElementById('calculate-fare-btn');
    
    if (calculateFareBtn) {
        calculateFareBtn.addEventListener('click', function() {
            var from = document.getElementById('fare-from').value;
            var to = document.getElementById('fare-to').value;
            var passengers = document.getElementById('fare-passengers').value;
            
            if (from && to && passengers) {
                if (from === to) {
                    alert('Departure and arrival stations cannot be the same');
                    return;
                }
                
                // Show loading spinner
                document.getElementById('fare-result').innerHTML = '<div class="text-center"><div class="loading-spinner mx-auto"></div><p class="mt-2">Calculating fare...</p></div>';
                
                // Simulate AJAX request
                setTimeout(function() {
                    // In a real application, this would be an AJAX call to the server
                    var basePrice = 10;
                    var distancePrice = Math.abs(parseInt(from) - parseInt(to)) * 5;
                    var totalPrice = (basePrice + distancePrice) * parseInt(passengers);
                    
                    var resultHtml = `
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Fare Details</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <tr>
                                        <th>Base Fare:</th>
                                        <td>$${basePrice.toFixed(2)}</td>
                                    </tr>
                                    <tr>
                                        <th>Distance Fare:</th>
                                        <td>$${distancePrice.toFixed(2)}</td>
                                    </tr>
                                    <tr>
                                        <th>Passengers:</th>
                                        <td>${passengers}</td>
                                    </tr>
                                    <tr>
                                        <th>Total Fare:</th>
                                        <td class="fw-bold">$${totalPrice.toFixed(2)}</td>
                                    </tr>
                                </table>
                                <div class="text-center mt-3">
                                    <a href="user/booking.php?from=${from}&to=${to}&passengers=${passengers}" class="btn btn-primary">Book Now</a>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('fare-result').innerHTML = resultHtml;
                }, 1000);
            } else {
                alert('Please select both stations and number of passengers');
            }
        });
    }

    // Ticket booking form
    var bookingForm = document.getElementById('booking-form');
    
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(event) {
            var from = document.getElementById('booking-from').value;
            var to = document.getElementById('booking-to').value;
            
            if (from === to) {
                event.preventDefault();
                alert('Departure and arrival stations cannot be the same');
            }
        });
    }

    // Password strength meter
    var passwordInput = document.getElementById('password');
    var passwordStrength = document.getElementById('password-strength');
    
    if (passwordInput && passwordStrength) {
        passwordInput.addEventListener('input', function() {
            var password = passwordInput.value;
            var strength = 0;
            
            if (password.length >= 8) {
                strength += 1;
            }
            
            if (password.match(/[a-z]+/)) {
                strength += 1;
            }
            
            if (password.match(/[A-Z]+/)) {
                strength += 1;
            }
            
            if (password.match(/[0-9]+/)) {
                strength += 1;
            }
            
            if (password.match(/[^a-zA-Z0-9]+/)) {
                strength += 1;
            }
            
            switch (strength) {
                case 0:
                case 1:
                    passwordStrength.className = 'progress-bar bg-danger';
                    passwordStrength.style.width = '20%';
                    passwordStrength.textContent = 'Very Weak';
                    break;
                case 2:
                    passwordStrength.className = 'progress-bar bg-warning';
                    passwordStrength.style.width = '40%';
                    passwordStrength.textContent = 'Weak';
                    break;
                case 3:
                    passwordStrength.className = 'progress-bar bg-info';
                    passwordStrength.style.width = '60%';
                    passwordStrength.textContent = 'Medium';
                    break;
                case 4:
                    passwordStrength.className = 'progress-bar bg-primary';
                    passwordStrength.style.width = '80%';
                    passwordStrength.textContent = 'Strong';
                    break;
                case 5:
                    passwordStrength.className = 'progress-bar bg-success';
                    passwordStrength.style.width = '100%';
                    passwordStrength.textContent = 'Very Strong';
                    break;
            }
        });
    }

    // Admin dashboard charts
    var bookingChart = document.getElementById('booking-chart');
    
    if (bookingChart) {
        var ctx = bookingChart.getContext('2d');
        
        // Sample data - this would come from the server in a real application
        var chartData = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Bookings',
                data: [65, 59, 80, 81, 56, 55],
                backgroundColor: 'rgba(52, 152, 219, 0.2)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 1
            }]
        };
        
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Data tables initialization
    var dataTables = document.querySelectorAll('.data-table');
    
    if (dataTables.length > 0 && typeof $.fn.DataTable !== 'undefined') {
        dataTables.forEach(function(table) {
            $(table).DataTable({
                responsive: true
            });
        });
    }

    // Print ticket button
    var printTicketBtn = document.getElementById('print-ticket-btn');
    
    if (printTicketBtn) {
        printTicketBtn.addEventListener('click', function() {
            window.print();
        });
    }

    // Search functionality
    var searchForm = document.getElementById('search-form');
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(event) {
            var searchQuery = document.getElementById('search-query').value.trim();
            
            if (searchQuery === '') {
                event.preventDefault();
                alert('Please enter a search query');
            }
        });
    }
});
