<?php
require_once __DIR__ . '/includes/functions.php';

$stats = $pdo->query("SELECT
        (SELECT COUNT(*) FROM users)                                  AS members,
        (SELECT COUNT(*) FROM reports WHERE status = 'Solved')        AS solved,
        (SELECT COUNT(*) FROM blood_donors)                           AS donors,
        (SELECT COUNT(*) FROM events WHERE status IN ('verified','approved')) AS events_done")->fetch();

$services = [
    ['service-report.jpg', 'Issue Reporting', 'Report broken roads, waste, flooding, harassment or corruption with a photo. Neighbours vote on urgency and the authority tracks it to resolution.'],
    ['service-events.jpg', 'Community Events', 'Organise clean-ups, health camps and awareness drives. Neighbours join, comment and help raise funds.'],
    ['service-sos.jpg', 'Emergency SOS', 'One tap sends an SOS with your location to verified local representatives and the admin team.'],
    ['service-blood.jpg', 'Blood Donor Network', 'Find compatible donors in your own area and request their contact. Donors stay in control of their number.'],
    ['service-chat.jpg', 'Neighbourhood Chat', 'Message verified local representatives in your area for quick, trusted coordination.'],
    ['service-location.jpg', 'Local First', 'Everything you see — reports, events, alerts and donors — is filtered to the area you live in.'],
];

$features = [
    ['feature-alerts.png', 'Real-time safety alerts', 'Authorities publish alerts for your division or area: floods, fires, road closures, outbreaks. Critical alerts appear at the top of your dashboard.'],
    ['feature-network.png', 'A trusted community network', 'Verified local representatives confirm completed work and approve fundraisers, so the community knows what is real.'],
    ['feature-sos.png', 'Emergency response', 'Nationwide hotlines and local emergency numbers are one tap away, next to the SOS button.'],
];

$testimonials = [
    ['testimonial-1.jpg', 'Nowar Sharmin', 'E-Shongskar transformed how our neighbourhood communicates. When we had a break-in attempt, the alert system helped us respond immediately and prevent theft.'],
    ['testimonial-2.jpg', 'Michael Chen', 'As someone who lives alone, the SOS feature gives me peace of mind. I know help is just one tap away if I ever need it.'],
    ['testimonial-3.jpg', 'Safia Kabir', "The blood donation network saved my nephew's life when he needed an urgent transfusion. The community response was overwhelming and heartwarming."],
];

$activeNav = 'home';
include __DIR__ . '/includes/header.php';
?>

<section class="landing-hero">
    <div class="container">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-seedling"></i> <?= e(APP_TAGLINE) ?></span>
            <h1>Empowering communities through <em>technology</em></h1>
            <p class="lead">E-Shongskar connects neighbours, improves safety and turns local complaints into collective action — so every area can fix its own problems, faster.</p>
            <div class="row">
                <?php if (current_user()): ?>
                    <a href="<?= url('dashboard.php') ?>" class="btn btn-accent btn-lg"><i class="fa-solid fa-gauge"></i> Go to my dashboard</a>
                    <a href="<?= url('report_new.php') ?>" class="btn btn-ghost-light btn-lg"><i class="fa-solid fa-flag"></i> Report an issue</a>
                <?php else: ?>
                    <a href="<?= url('register.php') ?>" class="btn btn-accent btn-lg"><i class="fa-solid fa-user-plus"></i> Join your community</a>
                    <a href="<?= url('login.php') ?>" class="btn btn-ghost-light btn-lg"><i class="fa-solid fa-right-to-bracket"></i> Log in</a>
                <?php endif; ?>
            </div>
        </div>
        <img class="hero-img" src="<?= asset('img/hero-phone.png') ?>" alt="E-Shongskar on a phone">
    </div>
</section>

<section class="landing-section alt" id="services">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow eyebrow-dark"><i class="fa-solid fa-layer-group"></i> Services</span>
            <h2>Everything your neighbourhood needs</h2>
            <p>Stay connected, safe and informed — and help fix what is broken around you.</p>
        </div>
        <div class="grid grid-3">
            <?php foreach ($services as [$img, $title, $text]): ?>
                <div class="card service-card">
                    <img src="<?= asset('img/' . $img) ?>" alt="">
                    <h3><?= e($title) ?></h3>
                    <p><?= e($text) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="landing-section" id="about">
    <div class="container grid grid-2" style="align-items:center;gap:48px">
        <div>
            <span class="eyebrow eyebrow-dark"><i class="fa-solid fa-circle-info"></i> How it works</span>
            <h2>From a complaint to a solved problem</h2>
            <p class="muted">Shongskar (সংস্কার) means reform. The platform gives every citizen a simple, accountable way to improve the place they live.</p>
            <div class="stack steps mt-3">
                <div class="step"><h4>Join your area</h4><p class="muted mb-0">Register with your NID and the area you live in. You will see your own community.</p></div>
                <div class="step"><h4>Report and vote</h4><p class="muted mb-0">Post a problem with a photo. Neighbours confirm it and rate how urgent it is.</p></div>
                <div class="step"><h4>Act together</h4><p class="muted mb-0">Organise an event, raise funds, and get the work verified by local representatives.</p></div>
                <div class="step"><h4>Get recognised</h4><p class="muted mb-0">Every contribution earns points. The most active people become Citizen of the Month.</p></div>
            </div>
        </div>
        <div class="stack">
            <?php foreach ($features as [$img, $title, $text]): ?>
                <div class="card feature-row">
                    <img src="<?= asset('img/' . $img) ?>" alt="">
                    <div>
                        <h3><?= e($title) ?></h3>
                        <p class="muted mb-0"><?= e($text) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="landing-section alt" id="impact">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow eyebrow-dark"><i class="fa-solid fa-chart-simple"></i> Our impact</span>
            <h2>Communities already making a difference</h2>
        </div>
        <div class="grid grid-4">
            <div class="card impact-card"><img src="<?= asset('img/impact-people.png') ?>" alt=""><div class="num"><?= number_format($stats['members']) ?></div><div class="muted">Community members</div></div>
            <div class="card impact-card"><img src="<?= asset('img/impact-solved.jpg') ?>" alt=""><div class="num"><?= number_format($stats['solved']) ?></div><div class="muted">Issues resolved</div></div>
            <div class="card impact-card"><img src="<?= asset('img/impact-blood.jpg') ?>" alt=""><div class="num"><?= number_format($stats['donors']) ?></div><div class="muted">Registered blood donors</div></div>
            <div class="card impact-card"><img src="<?= asset('img/service-events.jpg') ?>" alt=""><div class="num"><?= number_format($stats['events_done']) ?></div><div class="muted">Verified community projects</div></div>
        </div>
    </div>
</section>

<section class="landing-section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow eyebrow-dark"><i class="fa-solid fa-quote-left"></i> Community voices</span>
            <h2>What people say</h2>
        </div>
        <div class="grid grid-3">
            <?php foreach ($testimonials as [$img, $name, $quote]): ?>
                <div class="card testimonial">
                    <blockquote>&ldquo;<?= e($quote) ?>&rdquo;</blockquote>
                    <div class="who">
                        <img src="<?= asset('img/' . $img) ?>" alt="">
                        <strong><?= e($name) ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cta-band mt-4">
            <div>
                <h2>Your area needs you.</h2>
                <p>It takes two minutes to join and start improving your neighbourhood.</p>
            </div>
            <a href="<?= url(current_user() ? 'dashboard.php' : 'register.php') ?>" class="btn btn-accent btn-lg">
                <?= current_user() ? 'Open dashboard' : 'Create a free account' ?> <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
