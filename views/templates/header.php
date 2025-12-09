<?php
/**
 * Reusable Header Template
 * Include this at the top of every page
 */
require_once __DIR__ . '/../../config/db.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? "Aunt Joy's Restaurant"; ?></title>
    
    <script>
        (function() {
            try {
                const savedTheme = localStorage.getItem('auntJoyTheme');
                if (savedTheme) {
                    document.documentElement.setAttribute('data-theme', savedTheme);
                }
            } catch (error) {
                console.warn('Theme preference unavailable:', error);
            }
        })();
    </script>

    <!-- Base CSS -->
    <link rel="stylesheet" href="/aunt_joy/assets/css/style.css">
    
    <!-- Page-specific CSS -->
    <?php if(isset($customCSS)): ?>
        <link rel="stylesheet" href="/aunt_joy/assets/css/<?php echo $customCSS; ?>">
    <?php endif; ?>
    
    <!-- Base JavaScript -->
    <script src="/aunt_joy/assets/js/main.js" defer></script>
    
    <!-- Page-specific JavaScript -->
    <?php if(isset($customJS)): ?>
        <script src="/aunt_joy/assets/js/<?php echo $customJS; ?>" defer></script>
    <?php endif; ?>

    <?php
        $isLoggedIn = isLoggedIn();
        $currentRole = getCurrentUserRole();
        $currentUserId = getCurrentUserId();
        $displayName = $_SESSION['full_name'] ?? null;
        $cartUrl = ($isLoggedIn && $currentRole === 'Customer')
            ? '/aunt_joy/views/customer/cart.php'
            : '/aunt_joy/views/auth/login.php?next=cart';
    ?>

    <script>
        window.AUNT_JOY = {
            isLoggedIn: <?php echo json_encode($isLoggedIn); ?>,
            role: <?php echo json_encode($currentRole); ?>,
            userId: <?php echo json_encode($currentUserId); ?>,
            displayName: <?php echo json_encode($displayName); ?>
        };
    </script>
</head>
<body class="<?php echo $bodyClass ?? ''; ?>">
    
    <?php if(isset($showNav) && $showNav): ?>
    <!-- Navigation Bar -->
    <nav class="main-nav">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="/aunt_joy/index.php">
                    <span class="logo-icon">🍽️</span>
                    <span class="logo-text">Aunt Joy's</span>
                </a>
            </div>
            
            <div class="nav-menu">
                <div class="nav-links">
                    <a href="/aunt_joy/index.php#hero" class="nav-link">Home</a>
                    <a href="/aunt_joy/views/customer/menu.php" class="nav-link">Menu</a>
                    <a href="/aunt_joy/index.php#contact" class="nav-link">Contact</a>
                </div>

                <div class="nav-actions">
                    <button id="themeToggle" class="theme-toggle" aria-label="Toggle theme">
                        <span class="theme-icon"><img src="assets/images/icons/moon_16740252.png"></span>
                    </button>
                    

                    <?php if($isLoggedIn): ?>
                        <div class="nav-user-chip">
                            <span class="user-avatar">
                                <?php echo strtoupper(substr($displayName ?? 'AJ', 0, 1)); ?>
                            </span>
                            <div class="user-meta">
                                <strong><?php echo $displayName ?? 'Guest'; ?></strong>
                                <small><?php echo $currentRole ?? 'Customer'; ?></small>
                            </div>
                        </div>
                        <a href="/aunt_joy/controllers/auth/logout.php" class="btn btn-secondary nav-auth-btn">Logout</a>
                    <?php else: ?>
                        <a href="/aunt_joy/views/auth/login.php" class="nav-link">Login</a>
                        <a href="/aunt_joy/views/auth/register.php" class="btn btn-primary nav-auth-btn">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Main Content Wrapper -->
    <div class="content-wrapper">