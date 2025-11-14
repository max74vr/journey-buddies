    </main>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Compagni di Viaggi</h3>
                    <p>La community per trovare compagni di viaggio e vivere avventure indimenticabili insieme.</p>
                </div>

                <div class="footer-section">
                    <h4>Esplora</h4>
                    <ul>
                        <li><a href="<?= SITE_URL ?>/travels.php">Scopri Viaggi</a></li>
                        <li><a href="<?= SITE_URL ?>/explore-travelers.php">Trova Viaggiatori</a></li>
                        <li><a href="<?= SITE_URL ?>/create-travel.php">Crea un Viaggio</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Supporto</h4>
                    <ul>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Come Funziona</a></li>
                        <li><a href="#">Sicurezza</a></li>
                        <li><a href="#">Contatti</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Legale</h4>
                    <ul>
                        <li><a href="#">Termini di Servizio</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Compagni di Viaggi. Tutti i diritti riservati.</p>
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
