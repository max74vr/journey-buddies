    </main>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Journey Buddies</h3>
                    <p>The community to find travel companions and experience unforgettable adventures together.</p>
                </div>

                <div class="footer-section">
                    <h4>Explore</h4>
                    <ul>
                        <li><a href="<?= SITE_URL ?>/travels.php">Discover Journeys</a></li>
                        <li><a href="<?= SITE_URL ?>/explore-travelers.php">Find Travelers</a></li>
                        <li><a href="<?= SITE_URL ?>/create-travel.php">Create a Journey</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">How It Works</a></li>
                        <li><a href="#">Safety</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Journey Buddies. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="<?= SITE_URL ?>/public/js/main.js"></script>
    <?php if (isLoggedIn()): ?>
    <script>
        // Poll for unread messages
        function updateUnreadCount() {
            fetch('<?= SITE_URL ?>/api/unread-count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('unread-count');
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                });
        }

        // Update every 30 seconds
        updateUnreadCount();
        setInterval(updateUnreadCount, 30000);
    </script>
    <?php endif; ?>
</body>
</html>
