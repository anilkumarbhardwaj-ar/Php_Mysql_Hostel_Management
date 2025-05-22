<nav>
    <ul>
        <li><a href="index.php?page=dashboard">Dashboard</a></li>
        
        <?php if ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'staff'): ?>
        <li><a href="index.php?page=students">Students</a></li>
        <li><a href="index.php?page=courses">Courses</a></li>
        <?php endif; ?>
        
        <?php if ($_SESSION['user_role'] == 'admin'): ?>
        <li><a href="index.php?page=staff">Staff</a></li>
        <li><a href="index.php?page=guests">Guests</a></li>
        <li><a href="index.php?page=rooms">Rooms</a></li>
        <li><a href="index.php?page=buildings">Buildings</a></li>
        <li><a href="index.php?page=fees">Fees</a></li>
        <li><a href="index.php?page=reports">Reports</a></li>
        <li><a href="index.php?page=users">Users</a></li>
        <li><a href="index.php?page=settings">Settings</a></li>
        <?php endif; ?>
        
        <?php if ($_SESSION['user_role'] == 'student'): ?>
        <li><a href="index.php?page=room_details">Room Details</a></li>
        <li><a href="index.php?page=fee_details">Fee Details</a></li>
        <li><a href="index.php?page=course_details">Course Details</a></li>
        <?php endif; ?>
        
        <li><a href="index.php?page=profile">Profile</a></li>
    </ul>
</nav>

<style>
.main-nav {
    position: relative;
    background: linear-gradient(to right, #1e40af, #3b82f6);
    padding: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    z-index: 1000;
}

.nav-links {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
}

.nav-links li {
    margin: 0;
    transition: transform 0.2s ease;
}

.nav-links li:hover {
    transform: translateX(5px);
}

.nav-links a {
    color: #ffffff;
    text-decoration: none;
    padding: 0.8rem 1.2rem;
    display: block;
    font-weight: 500;
    transition: all 0.3s ease;
    border-radius: 4px;
    font-size: 0.95rem;
}

.nav-links a:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.hamburger {
    display: none;
    cursor: pointer;
    padding: 12px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    transition: all 0.3s ease;
    position: absolute;
    right: 1rem;
    top: 1rem;
    z-index: 1001;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.hamburger:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
    transform: scale(1.05);
}

.hamburger span {
    display: block;
    width: 28px;
    height: 3px;
    background: #ffffff;
    margin: 6px 0;
    transition: 0.3s;
    border-radius: 3px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

@media screen and (max-width: 768px) {
    .hamburger {
        display: block;
    }

    .nav-links {
        display: none;
        width: 100%;
        background: linear-gradient(to bottom, #1e40af, #2563eb);
        border-radius: 8px;
        margin-top: 10px;
        padding: 10px 0;
        position: absolute;
        top: 100%;
        left: 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .nav-links.active {
        display: flex;
        animation: slideDown 0.3s ease-out;
    }

    .nav-links li {
        width: 100%;
        text-align: left;
    }

    .nav-links li:hover {
        transform: translateX(0) scale(1.02);
        background: rgba(255, 255, 255, 0.1);
    }

    .nav-links a {
        padding: 1rem 1.5rem;
        color: #ffffff;
        font-size: 1rem;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .nav-links a:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
    }
}

@media screen and (min-width: 769px) {
    .nav-links {
        flex-direction: row;
        justify-content: flex-start;
        align-items: center;
    }

    .nav-links li {
        margin-right: 0.5rem;
    }

    .nav-links a {
        padding: 0.6rem 1rem;
    }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Additional styles for hamburger animation */
.hamburger span.rotate-45 {
    transform: rotate(45deg) translate(6px, 6px);
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.hamburger span.rotate-negative-45 {
    transform: rotate(-45deg) translate(6px, -6px);
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.hamburger span.hide {
    opacity: 0;
    transform: translateX(-10px);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');

    hamburger.addEventListener('click', function() {
        navLinks.classList.toggle('active');
        
        // Animate hamburger to X
        const spans = this.querySelectorAll('span');
        spans[0].classList.toggle('rotate-45');
        spans[1].classList.toggle('hide');
        spans[2].classList.toggle('rotate-negative-45');
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!hamburger.contains(event.target) && !navLinks.contains(event.target) && navLinks.classList.contains('active')) {
            navLinks.classList.remove('active');
            const spans = hamburger.querySelectorAll('span');
            spans[0].classList.remove('rotate-45');
            spans[1].classList.remove('hide');
            spans[2].classList.remove('rotate-negative-45');
        }
    });
});
</script>
