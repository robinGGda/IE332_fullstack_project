<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Character encoding and responsive viewport meta tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Page title -->
    <title>Digital Engineering Solutions</title>

    <!-- External library imports -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css"> 
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- Website header -->
    <header>
        <h1>Digital Engineering Solutions</h1>
    </header>
    
    <!-- Navigation menu -->
    <nav>
        <ul>
            <!-- Navigation links to different pages -->
            <li><a href="index.php" class="index">Home</a></li>
            <li><a href="dashboard.php" class="dashboard">Dashboard</a></li>
            <li><a href="machines_productivity.php" class="machines_productivity">Machines Productivity</a></li>
            <li><a href="QC_data.php" class="QC_data">QC Data</a></li>
            <li><a href="inventory.php" class="inventory">Inventory</a></li>
            <li><a href="sensors_and_faults.php" class="sensors_and_faults">Sensors & Faults</a></li>
            <li><a href="data_manipulation_deletion.php" class="data_manipulation_deletion">Data Deletion</a></li>
            <li><a href="data_manipulation_addition.php" class="data_manipulation_addition">Data Addition</a></li>
        </ul>
    </nav>

    <!-- Hero section with main headline and CTA buttons -->
    <section class="hero-section">
        <div class="hero-content" data-aos="fade-up">
            <h1>Transform Your Bottling Facility</h1>
            <p>Leverage cutting-edge digital solutions to optimize operations, boost efficiency, and drive growth in your manufacturing process.</p>
            <div class="cta-buttons">
                <a href="#features" class="cta-button cta-primary">Explore Solutions</a>
                <a href="#contact" class="cta-button cta-secondary">Meet Us</a>
            </div>
        </div>
    </section>

    <!-- Features section highlighting key solutions -->
    <section class="features-section" id="features">
        <div class="section-title" data-aos="fade-up">
            <h2>Our Solutions</h2>
            <p>Comprehensive digital tools for modern manufacturing</p>
        </div>
        <div class="features-grid">
            <!-- Individual feature cards -->
            <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-icon">📊</div>
                <h3>Real-time Analytics</h3>
                <p>Monitor your facility's performance with advanced analytics and real-time data visualization.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-icon">⚡</div>
                <h3>Process Optimization</h3>
                <p>Identify bottlenecks and optimize your production processes for maximum efficiency.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-icon">🔍</div>
                <h3>Quality Control</h3>
                <p>Maintain highest quality standards with our advanced monitoring and control systems.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
                <div class="feature-icon">📱</div>
                <h3>Mobile Access</h3>
                <p>Access your facility's data and controls from anywhere, at any time.</p>
            </div>
        </div>
    </section>

    <!-- Team section showcasing team members -->
    <section class="team-section" id="team">
        <div class="team-container">
            <div class="section-title" data-aos="fade-up">
                <h2>Meet Our Team</h2>
                <p>Expert professionals dedicated to your success</p>
            </div>
            <div class="team-grid">
                <!-- Individual team member cards -->
                <div class="team-member" data-aos="fade-up" data-aos-delay="100">
                    <img src="team_member1 2.png" alt="Team Member 1">
                    <h3>Kai</h3>
                    <p>Technical Lead</p>
                </div>
                <div class="team-member" data-aos="fade-up" data-aos-delay="200">
                    <img src="team_member2.JPG" alt="Team Member 2">
                    <h3>Gonzalo</h3>
                    <p>Process Engineer</p>
                </div>
                <div class="team-member" data-aos="fade-up" data-aos-delay="300">
                    <img src="team_member3.JPG" alt="Team Member 3">
                    <h3>Ashwin</h3>
                    <p>Data Scientist</p>
                </div>
                <div class="team-member" data-aos="fade-up" data-aos-delay="400">
                    <img src="team_member4.jpg" alt="Team Member 4">
                    <h3>Laurencio</h3>
                    <p>Quality Specialist</p>
                </div>
                <div class="team-member" data-aos="fade-up" data-aos-delay="400">
                    <img src="team_member5.jpeg" alt="Team Member 5">
                    <h3>Kenny</h3>
                    <p>Quality Specialist</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact section with call-to-action -->
    <section class="contact-section" id="contact">
        <div class="contact-content" data-aos="fade-up">
            <h2>Ready to Transform Your Facility?</h2>
            <div class="cta-buttons">
                <a href="dashboard.php" class="cta-button cta-secondary">launch our solution!</a>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer>
        <p>&copy; 2024 Digital Engineering Solutions. All rights reserved.</p>
        <div class="container">
            <div class="label">Your IP Address is:</div>
            <div id="ip">Loading...</div>
            <div class="label">Traceroute:</div>
            <div id="traceroute">
                <?php echo shell_exec("traceroute " . $_SERVER['REMOTE_ADDR']); ?>
            </div>
        </div>
    </footer>

    <!-- JavaScript Section -->
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Active nav link
        const navLinks = document.querySelectorAll('nav ul li a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Fetch and display user's IP address
        $(document).ready(function() {
            $.getJSON('https://api.ipify.org?format=json', function(data) {
                $('#ip').text(data.ip);
            }).fail(function() {
                $('#ip').text('Unable to fetch IP');
            });
        });
    </script>
</body>

<?php
// Close database connection if open
$conn->close();
?>
</html>
