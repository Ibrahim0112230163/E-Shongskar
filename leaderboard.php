<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();

$period = input('period') === 'month' ? 'month' : 'all';
$scope = input('scope') === 'country' ? 'country' : 'area';

$rows = leaderboard($scope === 'area' ? $user['area'] : null, 50, $period === 'month' ? date('Y-m-01') : null);
$podium = array_slice($rows, 0, 3);

function lb_link(array $change): string
{
    return '?' . http_build_query(array_merge(['period' => $GLOBALS['period'], 'scope' => $GLOBALS['scope']], $change));
}

$pageTitle = 'Leaderboard';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1><i class="fa-solid fa-trophy"></i> Leaderboard</h1>
        <p>Every report, event and helping hand earns points. Thank you to the people who keep our communities moving.</p>
    </div>
</section>

<div class="container container-narrow">
    <div class="card mb-3" style="padding-bottom:0">
        <div class="row-between">
            <div class="tabs mb-0" style="border:0">
                <a href="<?= lb_link(['period' => 'all']) ?>" class="<?= $period === 'all' ? 'active' : '' ?>">All time</a>
                <a href="<?= lb_link(['period' => 'month']) ?>" class="<?= $period === 'month' ? 'active' : '' ?>">Citizen of the month (<?= date('F') ?>)</a>
            </div>
            <div class="tabs mb-0" style="border:0">
                <a href="<?= lb_link(['scope' => 'area']) ?>" class="<?= $scope === 'area' ? 'active' : '' ?>"><?= e($user['area']) ?></a>
                <a href="<?= lb_link(['scope' => 'country']) ?>" class="<?= $scope === 'country' ? 'active' : '' ?>">Bangladesh</a>
            </div>
        </div>
    </div>

    <?php if (!$rows): ?>
        <div class="card empty"><i class="fa-solid fa-medal"></i><h3>No points yet</h3><p>Report an issue or join an event to get on the board.</p></div>
    <?php else: ?>
        <div class="podium mb-3">
            <?php foreach ([1, 0, 2] as $i): if (!isset($podium[$i])) continue; $p = $podium[$i]; ?>
                <div class="podium-item <?= $i === 0 ? 'first' : '' ?>">
                    <div class="medal"><?= ['🥇', '🥈', '🥉'][$i] ?></div>
                    <?= avatar($p, $i === 0 ? 'xl' : 'lg') ?>
                    <strong><?= e($p['full_name']) ?><?= verified_tick($p) ?></strong>
                    <div class="small muted"><?= e($p['area']) ?></div>
                    <div class="score"><?= $p['total_score'] ?> pts</div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card card-flush">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>#</th><th>Citizen</th><th>Area</th><th style="text-align:right">Points</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $i => $r): ?>
                        <tr <?= (int) $r['id'] === (int) $user['id'] ? 'style="background:var(--brand-50)"' : '' ?>>
                            <td><span class="rank"><?= $i + 1 ?></span></td>
                            <td><div class="user-cell"><?= avatar($r, 'sm') ?><div><strong><?= e($r['full_name']) ?><?= verified_tick($r) ?></strong><small>@<?= e($r['username']) ?></small></div></div></td>
                            <td><?= e($r['area']) ?></td>
                            <td style="text-align:right"><strong><?= $r['total_score'] ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="card mt-3">
        <h3 class="card-title"><i class="fa-solid fa-circle-info"></i> How points work</h3>
        <div class="grid grid-3 mt-2 small">
            <div><strong>+5</strong> for each issue you report</div>
            <div><strong>+4</strong> for each event you organise</div>
            <div><strong>+6</strong> when your event is approved</div>
            <div><strong>+10</strong> for an approved fundraiser</div>
            <div><strong>+2</strong> for each event you join</div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
