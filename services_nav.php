<header>
    <div class="container">
        <nav>
            <div class="logo"><i class="fas fa-home"></i> Orphanage Management System</div>
            <div class="nav-links">
                <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
                <a href="index.php">Home</a>
                <a href="index.php#features">Features</a>
                <?php endif; ?>
                <a href="about.php">About</a>
                <a href="services.php">Services</a>
                <a href="users.php">Users</a>
                <a href="process.php">Process</a>
                <a href="contact.php">Contact</a>
            </div>
            <a href="login.php" class="login-btn">Login</a>
        </nav>
    </div>
</header>