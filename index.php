<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Handle logout before any content output
if (isset($_GET['page']) && $_GET['page'] === 'logout') {
    if (logoutUser()) {
        header('Location: index.php?page=login');
        exit;
    } else {
        header('Location: index.php?error=logout_failed');
        exit;
    }
}

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$userRole = $isLoggedIn ? $_SESSION['user_role'] : '';

// Handle routing
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Redirect to login if not authenticated (except for login and register pages)
if (!$isLoggedIn && !in_array($page, ['login', 'register', 'forgot_password', 'reset_password'])) {
    header('Location: index.php?page=login');
    exit;
}

// Role-based access control
if ($isLoggedIn) {
    $allowedPages = getAllowedPages($userRole);
    if (!in_array($page, $allowedPages)) {
        header('Location: index.php?page=dashboard');
        exit;
    }
}

// Include header
include 'includes/header.php';

// Include navigation
if ($isLoggedIn) {
    include 'includes/navigation.php';
}

// Load the requested page
$filePath = 'pages/' . $page . '.php';
if (file_exists($filePath)) {
    include $filePath;
} else {
    include 'pages/404.php';
}

// Include footer
include 'includes/footer.php';
?>
