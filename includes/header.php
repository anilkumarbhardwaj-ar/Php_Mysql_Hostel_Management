<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>IIT Mandi iHub & HCI Foundation </h1>
            </div>
            <?php if (isLoggedIn()): ?>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <a href="index.php?page=logout" class="btn btn-danger">Logout</a>
            </div>
            <?php endif; ?>
        </header>