</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a class="brand" href="<?= url('index.php') ?>">
                <img src="<?= asset('img/logo-mark.jpg') ?>" alt="">
                <span>E-Shongskar <small>সংস্কার</small></span>
            </a>
            <p class="muted">A platform for neighbours to report local problems, organise community work and keep each other safe. <?= e(APP_TAGLINE) ?>.</p>
        </div>
        <div>
            <h4>Platform</h4>
            <a href="<?= url('reports.php') ?>">Issue reports</a>
            <a href="<?= url('events.php') ?>">Community events</a>
            <a href="<?= url('blood.php') ?>">Blood donors</a>
            <a href="<?= url('leaderboard.php') ?>">Leaderboard</a>
        </div>
        <div>
            <h4>Safety</h4>
            <a href="<?= url('alerts.php') ?>">Public alerts</a>
            <a href="<?= url('emergency.php') ?>">Emergency contacts</a>
            <a href="<?= url('emergency.php#sos') ?>">SOS</a>
        </div>
        <div>
            <h4>National helplines</h4>
            <a href="tel:999"><i class="fa-solid fa-phone"></i> 999 — Emergency</a>
            <a href="tel:109"><i class="fa-solid fa-phone"></i> 109 — Women &amp; children</a>
            <a href="tel:333"><i class="fa-solid fa-phone"></i> 333 — Information</a>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>&copy; <?= date('Y') ?> E-Shongskar. All rights reserved.</span>
    </div>
</footer>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
