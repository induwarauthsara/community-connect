<?php
require_once 'config/database.php';
require_once 'includes/common.php';

$page_title = 'Help - Community Connect';
include 'includes/header.php';
?>
<head>
    <link rel="stylesheet" href="assets/css/help.css">

</head>

<body>
    <div class="hero">

        <div class="hero-title">How can we help you?</div>

    </div>
    <div class="main-content">
        <div class="section">
            <h2>About Community Connect</h2>
            <div style="display: flex; align-items: center; gap: 24px;">
                <img src="logo.png" alt="Community" style="width:90px; height:90px; border-radius:12px; object-fit:cover;">
                <p>
                    Community Connect is a volunteer coordination program designed to bring together volunteers and organizations for community service projects. Our platform helps you find, join, and manage volunteer opportunities in your area.
                </p>
            </div>
        </div>

        <div class="section">
            <h2>Volunteer & Organization Features</h2>
            <div class="table">
                <div class="table-header">
                    <div class="table-col table-col-header">For Volunteers</div>
                    <div class="table-col table-col-header">For Organizations</div>
                </div>
                <div class="table-row">
                    <div class="table-col">
                        Update your profile to showcase your skills and interests.
                    </div>
                    <div class="table-col">
                        Create and manage volunteer projects.
                    </div>
                </div>
                <div class="table-row">
                    <div class="table-col">
                        Communicate with project organizers through the messaging system.
                    </div>
                    <div class="table-col">
                        Approve or decline volunteer applications.
                    </div>
                </div>
                <div class="table-row">
                    <div class="table-col">
                        Check event details and requirements before attending.
                    </div>
                    <div class="table-col">
                        Send updates and reminders to volunteers.
                    </div>
                </div>
                <div class="table-row">
                    <div class="table-col">
                        Mark your attendance after each event to track your hours.
                    </div>
                    <div class="table-col">
                        Track volunteer participation and generate reports.
                    </div>
                </div>
            </div>
        </div>

        <div class="contact-cards">
            <button class="contact-card" onclick="window.open('https://maps.app.goo.gl/FMoVe9KwmvjdRW2v7' ,'_blank');">
                <div class="icon">
                    <!-- Location SVG -->
                    <svg width="40" height="40" fill="none" stroke="#226597" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 21s-6-5.686-6-10a6 6 0 1 1 12 0c0 4.314-6 10-6 10z" />
                        <circle cx="12" cy="11" r="2" />
                    </svg>
                </div>
                <div class="contact-title">OUR MAIN OFFICE</div>
            </button>
            <button class="contact-card" onclick="alert('Phone: 011 200 20 20 (Toll Free)');">
                <div class="icon">
                    <!-- Phone SVG -->
                    <svg width="40" height="40" fill="none" stroke="#226597" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.72 19.72 0 0 1 3.08 4.18 2 2 0 0 1 5 2h3a2 2 0 0 1 2 1.72c.13 1.05.37 2.07.72 3.05a2 2 0 0 1-.45 2.11l-1.27 1.27a16 16 0 0 0 6.29 6.29l1.27-1.27a2 2 0 0 1 2.11-.45c.98.35 2 .59 3.05.72A2 2 0 0 1 22 16.92z" />
                </div>
                <div class="contact-title">PHONE NUMBER</div>
            </button>
            <button class="contact-card" onclick="window.open('https://facebook.com/communityconnect', '_blank');">
                <div class="icon">
                    <!-- Facebook SVG -->
                    <svg width="40" height="40" fill="none" stroke="#226597" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="2" y="2" width="20" height="20" rx="5" />
                        <path d="M16 8h-2a2 2 0 0 0-2 2v2h4" />
                        <path d="M14 16v-4" />
                    </svg>
                </div>
                <div class="contact-title">FACEBOOK</div>
            </button>
            <button class="contact-card" onclick="window.location.href='mailto:support@communityconnect.org';">
                <div class="icon">
                    <!-- Email SVG -->
                    <svg width="40" height="40" fill="none" stroke="#226597" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="5" width="18" height="14" rx="2" />
                        <path d="M3 7l9 6 9-6" />
                    </svg>
                </div>
                <div class="contact-title">EMAIL</div>
            </button>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>