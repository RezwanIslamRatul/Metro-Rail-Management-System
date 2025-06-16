<?php
// Include required files
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

session_start();

// Set page title
$pageTitle = 'Frequently Asked Questions';

// Include header
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="text-center mb-4">Frequently Asked Questions</h1>
            <p class="text-center lead">Find answers to the most commonly asked questions about our Metro Rail service.</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="list-group sticky-top" style="top: 20px;">
                <a class="list-group-item list-group-item-action active" href="#general">
                    <i class="fas fa-info-circle me-2"></i>General Information
                </a>
                <a class="list-group-item list-group-item-action" href="#bookings">
                    <i class="fas fa-ticket-alt me-2"></i>Bookings & Tickets
                </a>
                <a class="list-group-item list-group-item-action" href="#fares">
                    <i class="fas fa-money-bill-wave me-2"></i>Fares & Payments
                </a>
                <a class="list-group-item list-group-item-action" href="#schedule">
                    <i class="fas fa-clock me-2"></i>Schedule & Routes
                </a>
                <a class="list-group-item list-group-item-action" href="#services">
                    <i class="fas fa-concierge-bell me-2"></i>Services & Facilities
                </a>
                <a class="list-group-item list-group-item-action" href="#rules">
                    <i class="fas fa-gavel me-2"></i>Rules & Regulations
                </a>
                <a class="list-group-item list-group-item-action" href="#contact">
                    <i class="fas fa-phone-alt me-2"></i>Contact & Support
                </a>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- General Information -->
            <section id="general" class="mb-5">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3><i class="fas fa-info-circle me-2"></i>General Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accordionGeneral">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="general1">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGeneral1" aria-expanded="true" aria-controls="collapseGeneral1">
                                        What is Metro Rail System?
                                    </button>
                                </h2>
                                <div id="collapseGeneral1" class="accordion-collapse collapse show" aria-labelledby="general1" data-bs-parent="#accordionGeneral">
                                    <div class="accordion-body">
                                        <p>The Metro Rail System is a rapid transit system serving the metropolitan area, providing fast, reliable, and convenient transportation across the city. Our network connects major residential areas, business districts, educational institutions, shopping centers, and the international airport.</p>
                                        <p>The system features modern trains, well-maintained stations, and a commitment to safety, punctuality, and passenger comfort.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="general2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGeneral2" aria-expanded="false" aria-controls="collapseGeneral2">
                                        What are the operating hours?
                                    </button>
                                </h2>
                                <div id="collapseGeneral2" class="accordion-collapse collapse" aria-labelledby="general2" data-bs-parent="#accordionGeneral">
                                    <div class="accordion-body">
                                        <p>Our metro services operate from <strong>6:00 AM to 11:00 PM</strong> on weekdays (Monday to Friday) and <strong>7:00 AM to 10:00 PM</strong> on weekends (Saturday and Sunday).</p>
                                        <p>During special events or holidays, operating hours may be extended. Please check our announcements page or social media channels for any schedule changes.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="general3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGeneral3" aria-expanded="false" aria-controls="collapseGeneral3">
                                        How many lines does the Metro Rail have?
                                    </button>
                                </h2>
                                <div id="collapseGeneral3" class="accordion-collapse collapse" aria-labelledby="general3" data-bs-parent="#accordionGeneral">
                                    <div class="accordion-body">
                                        <p>Our Metro Rail System currently operates 4 main lines:</p>
                                        <ul>
                                            <li><strong>North-South Line (NSL):</strong> Connecting the northern and southern parts of the city</li>
                                            <li><strong>East-West Line (EWL):</strong> Connecting the eastern and western parts of the city</li>
                                            <li><strong>Circle Line (CCL):</strong> A circular route connecting major stations</li>
                                            <li><strong>Airport Express (AEL):</strong> Direct service to the international airport</li>
                                        </ul>
                                        <p>Each line is color-coded on our maps and signage for easy identification.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="general4">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGeneral4" aria-expanded="false" aria-controls="collapseGeneral4">
                                        How do I create an account on the website?
                                    </button>
                                </h2>
                                <div id="collapseGeneral4" class="accordion-collapse collapse" aria-labelledby="general4" data-bs-parent="#accordionGeneral">
                                    <div class="accordion-body">
                                        <p>Creating an account is simple:</p>
                                        <ol>
                                            <li>Click on the "Register" button in the top-right corner of the website.</li>
                                            <li>Fill in your personal details (name, email, phone number, etc.).</li>
                                            <li>Choose a secure password.</li>
                                            <li>Verify your email address by clicking the link sent to your email.</li>
                                            <li>Once verified, you can log in and access all account features.</li>
                                        </ol>
                                        <p>With a registered account, you can book tickets, view your booking history, save favorite routes, and more.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Bookings & Tickets -->
            <section id="bookings" class="mb-5">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3><i class="fas fa-ticket-alt me-2"></i>Bookings & Tickets</h3>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accordionBooking">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="booking1">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBooking1" aria-expanded="true" aria-controls="collapseBooking1">
                                        How do I book a ticket?
                                    </button>
                                </h2>
                                <div id="collapseBooking1" class="accordion-collapse collapse show" aria-labelledby="booking1" data-bs-parent="#accordionBooking">
                                    <div class="accordion-body">
                                        <p>Booking a ticket is easy:</p>
                                        <ol>
                                            <li>Log in to your account.</li>
                                            <li>Click on "Book Ticket" in your dashboard.</li>
                                            <li>Select your departure and arrival stations.</li>
                                            <li>Choose your journey date and number of passengers.</li>
                                            <li>Select from available schedules.</li>
                                            <li>Confirm your booking details and proceed to payment.</li>
                                            <li>Once payment is complete, your ticket(s) will be generated and available in your account.</li>
                                        </ol>
                                        <p>You can also book tickets at any station kiosk or ticket counter.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="booking2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBooking2" aria-expanded="false" aria-controls="collapseBooking2">
                                        Can I book tickets in advance?
                                    </button>
                                </h2>
                                <div id="collapseBooking2" class="accordion-collapse collapse" aria-labelledby="booking2" data-bs-parent="#accordionBooking">
                                    <div class="accordion-body">
                                        <p>Yes, you can book tickets up to 30 days in advance. Advance booking is recommended, especially for peak hours and weekends, to ensure seat availability.</p>
                                        <p>For recurrent travel needs, consider purchasing a monthly pass which offers unlimited travel on selected routes.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="booking3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBooking3" aria-expanded="false" aria-controls="collapseBooking3">
                                        How can I cancel or modify my booking?
                                    </button>
                                </h2>
                                <div id="collapseBooking3" class="accordion-collapse collapse" aria-labelledby="booking3" data-bs-parent="#accordionBooking">
                                    <div class="accordion-body">
                                        <p><strong>Cancellation:</strong></p>
                                        <ol>
                                            <li>Log in to your account and go to "Booking History."</li>
                                            <li>Find the booking you wish to cancel and click on "Cancel Booking."</li>
                                            <li>Confirm your cancellation.</li>
                                        </ol>
                                        
                                        <p><strong>Cancellation Policy:</strong></p>
                                        <ul>
                                            <li>Cancellations made at least 24 hours before departure: 100% refund</li>
                                            <li>Cancellations made between 12-24 hours before departure: 75% refund</li>
                                            <li>Cancellations made between 4-12 hours before departure: 50% refund</li>
                                            <li>Cancellations made less than 4 hours before departure: No refund</li>
                                        </ul>
                                        
                                        <p><strong>Modifications:</strong></p>
                                        <p>Direct modifications are not supported. Instead, you need to cancel your existing booking (subject to the cancellation policy) and make a new booking.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="booking4">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBooking4" aria-expanded="false" aria-controls="collapseBooking4">
                                        How do I check in for my journey?
                                    </button>
                                </h2>
                                <div id="collapseBooking4" class="accordion-collapse collapse" aria-labelledby="booking4" data-bs-parent="#accordionBooking">
                                    <div class="accordion-body">
                                        <p>You can check in for your journey in several ways:</p>
                                        <ul>
                                            <li><strong>Mobile Ticket:</strong> Show the QR code on your e-ticket to the staff at the station gate or scan it at the automated gates.</li>
                                            <li><strong>Printed Ticket:</strong> If you've printed your ticket, present it to the staff or scan the printed QR code.</li>
                                            <li><strong>Booking Reference:</strong> If you don't have your ticket, you can provide your booking reference and a valid ID at the station help desk.</li>
                                        </ul>
                                        <p>We recommend arriving at the station at least 15 minutes before your scheduled departure time to allow sufficient time for check-in.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Fares & Payments -->
            <section id="fares" class="mb-5">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3><i class="fas fa-money-bill-wave me-2"></i>Fares & Payments</h3>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accordionFares">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="fares1">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFares1" aria-expanded="true" aria-controls="collapseFares1">
                                        How are fares calculated?
                                    </button>
                                </h2>
                                <div id="collapseFares1" class="accordion-collapse collapse show" aria-labelledby="fares1" data-bs-parent="#accordionFares">
                                    <div class="accordion-body">
                                        <p>Our fare structure is based on the distance between stations. Longer journeys cost more than shorter ones. Different routes may have different fare structures, with the Airport Express having a special fare.</p>
                                        <p>To check the exact fare for your journey, you can use our "Fare Calculator" tool on the homepage, which will show you the fare between any two stations.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="fares2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFares2" aria-expanded="false" aria-controls="collapseFares2">
                                        What payment methods are accepted?
                                    </button>
                                </h2>
                                <div id="collapseFares2" class="accordion-collapse collapse" aria-labelledby="fares2" data-bs-parent="#accordionFares">
                                    <div class="accordion-body">
                                        <p>We accept the following payment methods:</p>
                                        <ul>
                                            <li><strong>Online Payments:</strong> Credit cards, debit cards, PayPal, bank transfers</li>
                                            <li><strong>At Station Counters:</strong> Cash, credit/debit cards, metro smart cards</li>
                                            <li><strong>At Ticket Vending Machines:</strong> Credit/debit cards, metro smart cards</li>
                                        </ul>
                                        <p>All payment transactions are secure and encrypted to protect your financial information.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="fares3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFares3" aria-expanded="false" aria-controls="collapseFares3">
                                        Are there any discounts or passes available?
                                    </button>
                                </h2>
                                <div id="collapseFares3" class="accordion-collapse collapse" aria-labelledby="fares3" data-bs-parent="#accordionFares">
                                    <div class="accordion-body">
                                        <p>Yes, we offer several discount options and passes:</p>
                                        <ul>
                                            <li><strong>Daily Pass:</strong> Unlimited travel for one day - $10</li>
                                            <li><strong>Weekly Pass:</strong> Unlimited travel for seven consecutive days - $45</li>
                                            <li><strong>Monthly Pass:</strong> Unlimited travel for one month - $150</li>
                                            <li><strong>Student Discount:</strong> 30% off regular fares with valid student ID</li>
                                            <li><strong>Senior Citizen Discount:</strong> 50% off regular fares for those aged 65+</li>
                                            <li><strong>Group Discount:</strong> 10% off for groups of 10 or more traveling together</li>
                                        </ul>
                                        <p>Passes can be purchased online through your account or at any station ticket counter.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="fares4">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFares4" aria-expanded="false" aria-controls="collapseFares4">
                                        How do refunds work?
                                    </button>
                                </h2>
                                <div id="collapseFares4" class="accordion-collapse collapse" aria-labelledby="fares4" data-bs-parent="#accordionFares">
                                    <div class="accordion-body">
                                        <p>Refunds are processed based on our cancellation policy:</p>
                                        <ul>
                                            <li>Refunds are credited back to the original payment method used for booking.</li>
                                            <li>Processing time for refunds is typically 3-5 business days, though it may take longer depending on your bank or payment provider.</li>
                                            <li>For cancelled bookings, refunds are processed automatically.</li>
                                            <li>In case of service disruptions or cancellations from our end, we offer full refunds regardless of timing.</li>
                                        </ul>
                                        <p>For any refund-related queries, please contact our customer support.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Schedule & Routes -->
            <section id="schedule" class="mb-5">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3><i class="fas fa-clock me-2"></i>Schedule & Routes</h3>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accordionSchedule">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="schedule1">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSchedule1" aria-expanded="true" aria-controls="collapseSchedule1">
                                        How frequent are the trains?
                                    </button>
                                </h2>
                                <div id="collapseSchedule1" class="accordion-collapse collapse show" aria-labelledby="schedule1" data-bs-parent="#accordionSchedule">
                                    <div class="accordion-body">
                                        <p>Train frequency varies by line and time of day:</p>
                                        <ul>
                                            <li><strong>Peak Hours (7:00 AM - 9:30 AM & 5:00 PM - 7:30 PM):</strong>
                                                <ul>
                                                    <li>North-South Line & East-West Line: Every 3-5 minutes</li>
                                                    <li>Circle Line: Every 5-7 minutes</li>
                                                    <li>Airport Express: Every 10 minutes</li>
                                                </ul>
                                            </li>
                                            <li><strong>Off-Peak Hours:</strong>
                                                <ul>
                                                    <li>North-South Line & East-West Line: Every 7-10 minutes</li>
                                                    <li>Circle Line: Every 10-12 minutes</li>
                                                    <li>Airport Express: Every 15 minutes</li>
                                                </ul>
                                            </li>
                                            <li><strong>Weekends:</strong>
                                                <ul>
                                                    <li>All Lines: Every 8-12 minutes</li>
                                                    <li>Airport Express: Every 15 minutes</li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="schedule2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSchedule2" aria-expanded="false" aria-controls="collapseSchedule2">
                                        Where can I find the complete route map?
                                    </button>
                                </h2>
                                <div id="collapseSchedule2" class="accordion-collapse collapse" aria-labelledby="schedule2" data-bs-parent="#accordionSchedule">
                                    <div class="accordion-body">
                                        <p>You can find our complete route map in several places:</p>
                                        <ul>
                                            <li><strong>Website:</strong> Visit the "Routes & Maps" section on our website.</li>
                                            <li><strong>Mobile App:</strong> Download our official mobile app for interactive maps.</li>
                                            <li><strong>Stations:</strong> Large route maps are displayed at all stations.</li>
                                            <li><strong>Printed Maps:</strong> Free pocket-sized route maps are available at all station information counters.</li>
                                        </ul>
                                        <p>Our route maps are color-coded for easy identification of different lines, and include all stations, interchange points, and major landmarks.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="schedule3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSchedule3" aria-expanded="false" aria-controls="collapseSchedule3">
                                        How do I plan my journey?
                                    </button>
                                </h2>
                                <div id="collapseSchedule3" class="accordion-collapse collapse" aria-labelledby="schedule3" data-bs-parent="#accordionSchedule">
                                    <div class="accordion-body">
                                        <p>Planning your journey is simple with our tools:</p>
                                        <ol>
                                            <li><strong>Journey Planner:</strong> Use our online Journey Planner on the website or mobile app. Enter your starting point and destination, and it will show you the best route, estimated travel time, fare, and transfer information.</li>
                                            <li><strong>Station Kiosks:</strong> Interactive journey planners are available at all stations.</li>
                                            <li><strong>Station Staff:</strong> Our station staff are always ready to help you plan your journey.</li>
                                        </ol>
                                        <p>For regular travelers, you can save frequent routes in your account for quick access.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="schedule4">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSchedule4" aria-expanded="false" aria-controls="collapseSchedule4">
                                        What happens if I miss my train?
                                    </button>
                                </h2>
                                <div id="collapseSchedule4" class="accordion-collapse collapse" aria-labelledby="schedule4" data-bs-parent="#accordionSchedule">
                                    <div class="accordion-body">
                                        <p>If you miss your train, don't worry:</p>
                                        <ul>
                                            <li>Your ticket is valid for any train on the same route, on the same day.</li>
                                            <li>You can simply wait for the next train, which will arrive based on the frequency schedule.</li>
                                            <li>There is no need to rebook or modify your ticket.</li>
                                        </ul>
                                        <p>However, if you have a specific seat assignment (for premium services), you may need to approach the station help desk for assistance in getting a seat on the next available train.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Services & Facilities -->
            <section id="services" class="mb-5">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3><i class="fas fa-concierge-bell me-2"></i>Services & Facilities</h3>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accordionServices">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="services1">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseServices1" aria-expanded="true" aria-controls="collapseServices1">
                                        What facilities are available at stations?
                                    </button>
                                </h2>
                                <div id="collapseServices1" class="accordion-collapse collapse show" aria-labelledby="services1" data-bs-parent="#accordionServices">
                                    <div class="accordion-body">
                                        <p>Our stations offer various facilities for passenger convenience:</p>
                                        <ul>
                                            <li><strong>Basic Facilities:</strong> Restrooms, water fountains, seating areas, information counters</li>
                                            <li><strong>Accessibility:</strong> Elevators, ramps, tactile paths for the visually impaired, accessible restrooms</li>
                                            <li><strong>Connectivity:</strong> Free Wi-Fi, mobile charging stations</li>
                                            <li><strong>Safety:</strong> CCTV surveillance, first aid stations, emergency help points</li>
                                            <li><strong>Conveniences:</strong> ATMs, vending machines, small retail shops, food kiosks</li>
                                            <li><strong>Information:</strong> Digital information displays, route maps, help desks</li>
                                        </ul>
                                        <p>Major stations also offer additional facilities like lounges, larger shopping areas, and food courts.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="services2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseServices2" aria-expanded="false" aria-controls="collapseServices2">
                                        Is there Wi-Fi on trains and at stations?
                                    </button>
                                </h2>
                                <div id="collapseServices2" class="accordion-collapse collapse" aria-labelledby="services2" data-bs-parent="#accordionServices">
                                    <div class="accordion-body">
                                        <p><strong>At Stations:</strong> Yes, all stations provide free Wi-Fi. Connect to the "MetroRail-Free" network and follow the authentication process.</p>
                                        <p><strong>On Trains:</strong> Wi-Fi is available on all trains on the Airport Express Line and newer trains on other lines. Look for the Wi-Fi symbol or ask the train staff if you're unsure.</p>
                                        <p><strong>Usage Limits:</strong> Free Wi-Fi has a fair usage policy of 100MB per session with moderate speed. For higher speeds or unlimited data, premium Wi-Fi passes can be purchased at stations or through our mobile app.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="services3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseServices3" aria-expanded="false" aria-controls="collapseServices3">
                                        What about accessibility for disabled passengers?
                                    </button>
                                </h2>
                                <div id="collapseServices3" class="accordion-collapse collapse" aria-labelledby="services3" data-bs-parent="#accordionServices">
                                    <div class="accordion-body">
                                        <p>We are committed to making our services accessible to all passengers:</p>
                                        <ul>
                                            <li><strong>Stations:</strong> All stations are equipped with elevators, ramps, tactile guidance paths, and accessible restrooms.</li>
                                            <li><strong>Trains:</strong> All trains have designated spaces for wheelchairs and priority seating for those who need it.</li>
                                            <li><strong>Visual & Hearing Impairments:</strong> Stations feature tactile maps, Braille signage, and audio announcements. Visual displays complement all audio announcements on trains.</li>
                                            <li><strong>Assistance:</strong> Staff are trained to assist passengers with disabilities. Assistance can be pre-booked through our customer service or requested at any station help desk.</li>
                                            <li><strong>Service Animals:</strong> Trained service animals are welcome on all our trains and stations.</li>
                                        </ul>
                                        <p>For specific accessibility requirements or assistance, please contact our dedicated accessibility helpline at 1-800-METRO-HELP.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="services4">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseServices4" aria-expanded="false" aria-controls="collapseServices4">
                                        Can I bring luggage on the train?
                                    </button>
                                </h2>
                                <div id="collapseServices4" class="accordion-collapse collapse" aria-labelledby="services4" data-bs-parent="#accordionServices">
                                    <div class="accordion-body">
                                        <p>Yes, passengers can bring luggage on trains, subject to the following guidelines:</p>
                                        <ul>
                                            <li><strong>Size Limits:</strong> Luggage should not exceed 60 x 40 x 25 cm (standard carry-on size) on regular lines. The Airport Express Line accommodates larger luggage.</li>
                                            <li><strong>Weight Limit:</strong> Maximum 20kg per piece of luggage.</li>
                                            <li><strong>Quantity:</strong> Each passenger may carry up to two pieces of luggage.</li>
                                            <li><strong>Placement:</strong> Luggage should be placed in designated storage areas or under seats, and should not block aisles or doors.</li>
                                            <li><strong>Peak Hours:</strong> We recommend avoiding travel with large luggage during peak hours (7:00-9:30 AM and 5:00-7:30 PM).</li>
                                        </ul>
                                        <p>For oversized or excessive luggage, additional charges may apply. Please inquire at the station help desk.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Rules & Regulations -->
            <section id="rules" class="mb-5">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3><i class="fas fa-gavel me-2"></i>Rules & Regulations</h3>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accordionRules">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="rules1">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRules1" aria-expanded="true" aria-controls="collapseRules1">
                                        What are the general rules for passengers?
                                    </button>
                                </h2>
                                <div id="collapseRules1" class="accordion-collapse collapse show" aria-labelledby="rules1" data-bs-parent="#accordionRules">
                                    <div class="accordion-body">
                                        <p>All passengers must adhere to the following rules:</p>
                                        <ul>
                                            <li>Always have a valid ticket or pass for your journey.</li>
                                            <li>Follow instructions from metro staff and signage.</li>
                                            <li>Keep clear of closing doors and stand behind the yellow line on platforms.</li>
                                            <li>Do not eat or drink in trains (water is permitted).</li>
                                            <li>Do not smoke or vape in trains or stations.</li>
                                            <li>Do not litter; use waste bins provided.</li>
                                            <li>Keep noise levels reasonable; use headphones for music/videos.</li>
                                            <li>Offer seats to those who need them (elderly, pregnant, disabled, etc.).</li>
                                            <li>Report suspicious items or behavior to staff or security.</li>
                                            <li>In case of emergency, follow staff instructions or emergency procedures.</li>
                                        </ul>
                                        <p>Compliance with these rules ensures a safe and comfortable journey for all.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="rules2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRules2" aria-expanded="false" aria-controls="collapseRules2">
                                        What items are prohibited on trains?
                                    </button>
                                </h2>
                                <div id="collapseRules2" class="accordion-collapse collapse" aria-labelledby="rules2" data-bs-parent="#accordionRules">
                                    <div class="accordion-body">
                                        <p>The following items are prohibited on trains and in stations:</p>
                                        <ul>
                                            <li>Dangerous goods (explosives, flammable items, toxic substances)</li>
                                            <li>Weapons of any kind (including replicas)</li>
                                            <li>Bicycles (except folding bikes that are fully folded)</li>
                                            <li>Large sporting equipment</li>
                                            <li>Items with strong odors</li>
                                            <li>Pets (except service animals and small pets in appropriate carriers)</li>
                                            <li>Oversized items that cannot fit through the fare gates or obstruct pathways</li>
                                            <li>Open containers of food or drink (sealed containers are permitted)</li>
                                            <li>Items that may cause injury or discomfort to other passengers</li>
                                        </ul>
                                        <p>Security checks may be conducted, and prohibited items may be confiscated.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="rules3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRules3" aria-expanded="false" aria-controls="collapseRules3">
                                        Can I bring pets on the train?
                                    </button>
                                </h2>
                                <div id="collapseRules3" class="accordion-collapse collapse" aria-labelledby="rules3" data-bs-parent="#accordionRules">
                                    <div class="accordion-body">
                                        <p>Our pet policy is as follows:</p>
                                        <ul>
                                            <li><strong>Service Animals:</strong> Trained service animals are always welcome and do not require containment.</li>
                                            <li><strong>Small Pets:</strong> Small pets may be brought onboard under the following conditions:
                                                <ul>
                                                    <li>They must be carried in an appropriate pet carrier that is fully enclosed.</li>
                                                    <li>The carrier must be small enough to fit on your lap or under your seat.</li>
                                                    <li>The pet must remain in the carrier at all times.</li>
                                                    <li>Owners are responsible for their pet's behavior and cleanliness.</li>
                                                </ul>
                                            </li>
                                            <li><strong>Restrictions:</strong>
                                                <ul>
                                                    <li>Pets are not permitted during peak hours (7:00-9:30 AM and 5:00-7:30 PM on weekdays).</li>
                                                    <li>No more than one pet carrier per passenger.</li>
                                                </ul>
                                            </li>
                                        </ul>
                                        <p>Staff reserve the right to refuse entry if a pet is causing disturbance or posing a risk to other passengers.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="rules4">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRules4" aria-expanded="false" aria-controls="collapseRules4">
                                        What happens if I break the rules?
                                    </button>
                                </h2>
                                <div id="collapseRules4" class="accordion-collapse collapse" aria-labelledby="rules4" data-bs-parent="#accordionRules">
                                    <div class="accordion-body">
                                        <p>Consequences for breaking rules depend on the severity of the violation:</p>
                                        <ul>
                                            <li><strong>Minor Violations:</strong> For first-time minor infractions, you may receive a verbal warning.</li>
                                            <li><strong>Repeated Violations:</strong> Continued disregard for rules may result in being asked to leave the train or station.</li>
                                            <li><strong>Fare Evasion:</strong> Traveling without a valid ticket incurs a fine of $100 for first offenses, increasing for repeat offenders.</li>
                                            <li><strong>Damage to Property:</strong> Vandalism or damage to metro property is subject to fines and potential criminal charges.</li>
                                            <li><strong>Serious Violations:</strong> Actions that endanger safety, involve prohibited items, or harassment may result in immediate removal, fines, barring from the system, and potential legal action.</li>
                                        </ul>
                                        <p>All metro staff and security personnel are authorized to enforce these rules.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Contact & Support -->
            <section id="contact" class="mb-5">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3><i class="fas fa-phone-alt me-2"></i>Contact & Support</h3>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accordionContact">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="contact1">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContact1" aria-expanded="true" aria-controls="collapseContact1">
                                        How can I contact customer support?
                                    </button>
                                </h2>
                                <div id="collapseContact1" class="accordion-collapse collapse show" aria-labelledby="contact1" data-bs-parent="#accordionContact">
                                    <div class="accordion-body">
                                        <p>You can reach our customer support through multiple channels:</p>
                                        <ul>
                                            <li><strong>Phone:</strong> Call our 24/7 helpline at 1-800-METRO (1-800-638-76)</li>
                                            <li><strong>Email:</strong> support@metrorail.com</li>
                                            <li><strong>Live Chat:</strong> Available on our website and mobile app from 7:00 AM to 10:00 PM daily</li>
                                            <li><strong>In Person:</strong> Visit the customer service desk at any major station</li>
                                            <li><strong>Social Media:</strong> Message us on Facebook, Twitter, or Instagram @MetroRailOfficial</li>
                                        </ul>
                                        <p>For lost items, please call our dedicated lost and found service at 1-800-METRO-LOST.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="contact2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContact2" aria-expanded="false" aria-controls="collapseContact2">
                                        What should I do if I've lost something on a train or at a station?
                                    </button>
                                </h2>
                                <div id="collapseContact2" class="accordion-collapse collapse" aria-labelledby="contact2" data-bs-parent="#accordionContact">
                                    <div class="accordion-body">
                                        <p>If you've lost an item:</p>
                                        <ol>
                                            <li><strong>Report It:</strong> File a lost item report through one of these methods:
                                                <ul>
                                                    <li>Call our Lost & Found hotline: 1-800-METRO-LOST</li>
                                                    <li>Fill out the lost item form on our website or mobile app</li>
                                                    <li>Visit any station customer service desk</li>
                                                </ul>
                                            </li>
                                            <li><strong>Provide Details:</strong> Be ready to provide:
                                                <ul>
                                                    <li>Description of the item</li>
                                                    <li>Date, time, and location where it was lost</li>
                                                    <li>Train line and direction if applicable</li>
                                                    <li>Your contact information</li>
                                                </ul>
                                            </li>
                                            <li><strong>Check Status:</strong> You can check the status of your lost item:
                                                <ul>
                                                    <li>Online through our Lost & Found tracking system</li>
                                                    <li>By calling the Lost & Found hotline</li>
                                                </ul>
                                            </li>
                                            <li><strong>Claim Your Item:</strong> If your item is found, you'll be notified. To claim it:
                                                <ul>
                                                    <li>Visit the Central Lost & Found office or designated station</li>
                                                    <li>Bring identification and proof of ownership if possible</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        <p>Found items are kept for 30 days before being donated or disposed of.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="contact3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContact3" aria-expanded="false" aria-controls="collapseContact3">
                                        How do I report an issue or submit feedback?
                                    </button>
                                </h2>
                                <div id="collapseContact3" class="accordion-collapse collapse" aria-labelledby="contact3" data-bs-parent="#accordionContact">
                                    <div class="accordion-body">
                                        <p>We welcome your feedback and reports of any issues:</p>
                                        <ul>
                                            <li><strong>Online Feedback Form:</strong> Visit the "Contact Us" section on our website to submit detailed feedback.</li>
                                            <li><strong>Mobile App:</strong> Use the "Report an Issue" feature in our mobile app, which allows you to attach photos.</li>
                                            <li><strong>Email:</strong> Send your feedback to feedback@metrorail.com.</li>
                                            <li><strong>Phone:</strong> Call our customer service at 1-800-METRO to report issues.</li>
                                            <li><strong>In Person:</strong> Speak to station managers or customer service representatives at any station.</li>
                                            <li><strong>Feedback Cards:</strong> Fill out comment cards available at station customer service desks.</li>
                                        </ul>
                                        <p>For urgent safety concerns or emergencies, please inform metro staff immediately or use the emergency help points located on platforms and in trains.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="contact4">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContact4" aria-expanded="false" aria-controls="collapseContact4">
                                        What should I do in case of an emergency?
                                    </button>
                                </h2>
                                <div id="collapseContact4" class="accordion-collapse collapse" aria-labelledby="contact4" data-bs-parent="#accordionContact">
                                    <div class="accordion-body">
                                        <p>In case of an emergency:</p>
                                        <ol>
                                            <li><strong>On Trains:</strong>
                                                <ul>
                                                    <li>Alert the train operator using the emergency intercom at the end of each car</li>
                                                    <li>Follow instructions from the train operator or staff</li>
                                                    <li>For medical emergencies, press the emergency button to alert staff</li>
                                                </ul>
                                            </li>
                                            <li><strong>At Stations:</strong>
                                                <ul>
                                                    <li>Use the emergency help points on platforms</li>
                                                    <li>Approach any staff member or security personnel</li>
                                                    <li>In case of platform evacuation, use marked emergency exits</li>
                                                </ul>
                                            </li>
                                            <li><strong>General Guidelines:</strong>
                                                <ul>
                                                    <li>Remain calm and assist others if safe to do so</li>
                                                    <li>Do not attempt to retrieve items from tracks</li>
                                                    <li>In case of fire, do not use elevators</li>
                                                    <li>For serious emergencies, call local emergency services (911)</li>
                                                </ul>
                                            </li>
                                        </ol>
                                        <p>All stations and trains are equipped with first aid kits, fire extinguishers, and emergency exits. Familiarize yourself with the location of these facilities when traveling.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Contact Form -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3><i class="fas fa-envelope me-2"></i>Still Have Questions?</h3>
                </div>
                <div class="card-body">
                    <p>If you couldn't find the answer to your question, please fill out the form below and we'll get back to you as soon as possible.</p>
                    
                    <form action="process-contact.php" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Your Question</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Submit Question
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active state for sidebar links
            document.querySelectorAll('.list-group-item').forEach(item => {
                item.classList.remove('active');
            });
            this.classList.add('active');
            
            // Scroll to the target section
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
    
    // Activate sidebar link based on scroll position
    window.addEventListener('scroll', function() {
        const sections = document.querySelectorAll('section');
        const navLinks = document.querySelectorAll('.list-group-item');
        
        let current = '';
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            
            if (pageYOffset >= (sectionTop - 200)) {
                current = section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    });
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>
