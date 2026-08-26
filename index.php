<?php
include "config/database.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Prepexus - Student Study Tracker</title>

    <link rel="stylesheet" type="text/css" href="css/style.css">

</head>

<body>

<?php
include "includes/navbar.php";
?>

<section class="hero">

    <div class="hero-text">

        <p class="small-title">YOUR PERSONAL STUDY COMPANION</p>

        <h1>Study smarter.<br>Achieve more.</h1>

        <p class="hero-description">
            Prepexus helps you organize your subjects, plan your tasks
            and keep your study materials in one simple place.
        </p>

        <div class="hero-buttons">

            <a href="register.php" class="btn primary-btn">
                Get Started
            </a>

            <a href="#features" class="btn secondary-btn">
                Explore Features
            </a>

        </div>

    </div>

    <div class="home-feature-card">

    <div class="feature-card-header">
        <div>
            <span class="feature-label">PREPEXUS</span>
            <h3>Everything in one place</h3>
        </div>

        <div class="feature-card-icon">
            ✦
        </div>
    </div>


    <div class="feature-item">

        <div class="feature-icon subject-icon">
            📚
        </div>

        <div>
            <h4>Manage Subjects</h4>
            <p>Keep all your subjects organized.</p>
        </div>

    </div>


    <div class="feature-item">

        <div class="feature-icon task-icon">
            ✓
        </div>

        <div>
            <h4>Plan Your Study</h4>
            <p>Create and manage study tasks.</p>
        </div>

    </div>


    <div class="feature-item">

        <div class="feature-icon progress-icon">
            ↗
        </div>

        <div>
            <h4>Track Progress</h4>
            <p>See how much you've accomplished.</p>
        </div>

    </div>


    <div class="feature-card-footer">

        <span>Simple</span>

        <span>•</span>

        <span>Organized</span>

        <span>•</span>

        <span>Focused</span>

    </div>

</div>

</section>


<section class="features" id="features">

    <div class="section-heading">

        <p class="small-title">SIMPLE & ORGANIZED</p>

        <h2>Everything you need to study better</h2>

        <p>
            Keep your study life organized with simple tools.
        </p>

    </div>


    <div class="feature-container">

        <div class="feature-card">

            <div class="feature-icon">📚</div>

            <h3>Subjects</h3>

            <p>
                Manage your subjects and keep track of your academic work.
            </p>

        </div>


        <div class="feature-card">

            <div class="feature-icon">✓</div>

            <h3>Study Tasks</h3>

            <p>
                Create tasks, set due dates and track your study progress.
            </p>

        </div>


        <div class="feature-card">

            <div class="feature-icon">📖</div>

            <h3>Materials</h3>

            <p>
                Save useful notes, videos and websites for quick access.
            </p>

        </div>

    </div>

</section>


<section class="cta">

    <h2>Ready to organize your study?</h2>

    <p>
        Create your Prepexus account and start tracking your study journey.
    </p>

    <a href="register.php" class="btn primary-btn">
        Create Account
    </a>

</section>


<footer>

    <p>© 2026 Prepexus. Student Study Tracker.</p>

</footer>

</body>
</html>
