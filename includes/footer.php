<footer class="main-footer">
    <div class="footer-content">
        <div class="footer-section">
            <h4>iHub IIT Mandi</h4>
            <p>Innovation Hub for Technology and Entrepreneurship</p>
        </div>
        <div class="footer-section">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="index.php?page=dashboard">Dashboard</a></li>
                <li><a href="index.php?page=contact">Contact</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h4>Connect With Us</h4>
            <div class="social-links">
                <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> iHub IIT Mandi. All rights reserved.</p>
    </div>
</footer>

<style>
.main-footer {
    background: linear-gradient(to right, #1e40af, #3b82f6);
    color: #ffffff;
    padding: 3rem 0 0 0;
    margin-top: 3rem;
    box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.footer-section h4 {
    color: #ffffff;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    position: relative;
    padding-bottom: 0.5rem;
}

.footer-section h4::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 50px;
    height: 2px;
    background: rgba(255, 255, 255, 0.3);
}

.footer-section p {
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.6;
    margin-bottom: 1rem;
}

.footer-section ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-section ul li {
    margin-bottom: 0.75rem;
}

.footer-section ul li a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-block;
}

.footer-section ul li a:hover {
    color: #ffffff;
    transform: translateX(5px);
}

.social-links {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.social-link {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.1);
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s ease;
}

.social-link:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-3px);
}

.footer-bottom {
    background: rgba(0, 0, 0, 0.1);
    padding: 1.5rem 0;
    margin-top: 3rem;
    text-align: center;
}

.footer-bottom p {
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .main-footer {
        padding: 2rem 0 0 0;
    }

    .footer-content {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        text-align: center;
    }

    .footer-section h4::after {
        left: 50%;
        transform: translateX(-50%);
    }

    .social-links {
        justify-content: center;
    }

    .footer-section ul li a:hover {
        transform: translateX(0) scale(1.05);
    }
}
</style>

<!-- Add Font Awesome for social icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script src="assets/js/main.js"></script>
</body>

</html>