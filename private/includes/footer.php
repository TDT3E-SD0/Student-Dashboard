<?php
/**
 * Footer Include File
 * 
 * Displays footer with copyright, links, and social media
 */
?>
    </main>
    
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4>About TDT3E</h4>
                <p>A comprehensive student dashboard designed to help you manage grades, tasks, and academic life efficiently.</p>
                <div class="social-links">
                    <a href="https://twitter.com" target="_blank" title="Twitter">
                        <span>𝕏</span>
                    </a>
                    <a href="https://facebook.com" target="_blank" title="Facebook">
                        <span>f</span>
                    </a>
                    <a href="https://instagram.com" target="_blank" title="Instagram">
                        <span>📷</span>
                    </a>
                    <a href="https://github.com" target="_blank" title="GitHub">
                        <span>⚙</span>
                    </a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Quick Links</h4>
                <a href="dashboard.php">Dashboard</a>
                <a href="pages/grades.php">Grades</a>
                <a href="pages/tasks.php">Tasks</a>
                <a href="pages/blog.php">Blog</a>
                <a href="pages/files.php">Files</a>
            </div>
            
            <div class="footer-section">
                <h4>Support</h4>
                <a href="#">Help Center</a>
                <a href="#">Documentation</a>
                <a href="#">Contact Us</a>
                <a href="#">Report Bug</a>
            </div>
            
            <div class="footer-section">
                <h4>Legal</h4>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Cookie Policy</a>
                <a href="#">Disclaimer</a>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> TDT3E - Student Dashboard. All rights reserved.</p>
            <p>Designed with <span style="color: var(--color-danger);">♥</span> by TDT3E Development Team</p>
        </div>
    </footer>

    <script src="<?php echo (strpos($_SERVER['PHP_SELF'], 'pages/') !== false) ? '../' : ''; ?>assets/js/main.js"></script>
</body>
</html>
