<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$errors = [];

if (is_post()) {
    verify_csrf();
    $action = input('action');

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM emergency_contacts WHERE id = ?')->execute([(int) input('contact_id')]);
        flash('success', 'Contact deleted.');
        redirect('admin/contacts.php');
    }

    $name = input('name');
    $category = in_array(input('category'), CONTACT_CATEGORIES, true) ? input('category') : 'Other';
    $phone = input('phone');
    $address = input('address');
    $division = in_array(input('division'), DIVISIONS, true) ? input('division') : null;
    $area = $division && input('area') !== '' ? normalize_area(input('area')) : null;

    if (mb_strlen($name) < 2)                     $errors[] = 'Please enter a name.';
    if (!preg_match('/^[\d+\-\s()]{3,30}$/', $phone)) $errors[] = 'Please enter a valid phone number.';

    if (!$errors) {
        if ($action === 'update') {
            $pdo->prepare('UPDATE emergency_contacts SET name = ?, category = ?, phone = ?, address = ?, division = ?, area = ? WHERE id = ?')
                ->execute([$name, $category, $phone, $address ?: null, $division, $area, (int) input('contact_id')]);
            flash('success', 'Contact updated.');
        } else {
            $pdo->prepare('INSERT INTO emergency_contacts (name, category, phone, address, division, area) VALUES (?,?,?,?,?,?)')
                ->execute([$name, $category, $phone, $address ?: null, $division, $area]);
            flash('success', 'Contact added.');
        }
        redirect('admin/contacts.php');
    }
}

$filter = input('division');
$sql = 'SELECT * FROM emergency_contacts';
$params = [];
if ($filter === 'national') {
    $sql .= ' WHERE division IS NULL';
} elseif (in_array($filter, DIVISIONS, true)) {
    $sql .= ' WHERE division = ?';
    $params[] = $filter;
}
$stmt = $pdo->prepare($sql . ' ORDER BY division IS NULL DESC, division, area, category, name');
$stmt->execute($params);
$contacts = $stmt->fetchAll();
$areas = $pdo->query('SELECT DISTINCT area FROM users ORDER BY area')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Emergency contacts';
$adminNav = 'contacts';
include __DIR__ . '/../includes/admin_header.php';

function contact_fields(string $prefix): void
{ ?>
    <div class="field"><label for="<?= $prefix ?>name">Name</label><input class="input" id="<?= $prefix ?>name" name="name" data-field="name" maxlength="150" required></div>
    <div class="field"><label for="<?= $prefix ?>category">Category</label>
        <select class="select" id="<?= $prefix ?>category" name="category" data-field="category">
            <?php foreach (CONTACT_CATEGORIES as $c): ?><option><?= $c ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="<?= $prefix ?>phone">Phone</label><input class="input" id="<?= $prefix ?>phone" name="phone" data-field="phone" required></div>
    <div class="field"><label for="<?= $prefix ?>address">Address / note</label><input class="input" id="<?= $prefix ?>address" name="address" data-field="address" maxlength="255"></div>
    <div class="field"><label for="<?= $prefix ?>division">Division</label>
        <select class="select" id="<?= $prefix ?>division" name="division" data-field="division"><option value="">Nationwide</option>
            <?php foreach (DIVISIONS as $d): ?><option><?= $d ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="<?= $prefix ?>area">Area (optional)</label><input class="input" id="<?= $prefix ?>area" name="area" data-field="area" list="areaList" placeholder="Leave blank for the whole division"></div>
<?php }
?>

<datalist id="areaList"><?php foreach ($areas as $a): ?><option value="<?= e($a) ?>"><?php endforeach; ?></datalist>

<div class="layout-sidebar" style="grid-template-columns:minmax(0,1fr) 360px">
    <div>
        <div class="card mb-3">
            <form class="filters" method="get">
                <div class="field"><label for="f_division">Show</label>
                    <select class="select" id="f_division" name="division" data-autosubmit>
                        <option value="">All contacts</option>
                        <option value="national" <?= selected('national', $filter) ?>>Nationwide only</option>
                        <?php foreach (DIVISIONS as $d): ?><option <?= selected($d, $filter) ?>><?= $d ?></option><?php endforeach; ?>
                    </select></div>
            </form>
        </div>
        <div class="card card-flush">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Name</th><th>Phone</th><th>Scope</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($contacts as $c): ?>
                        <tr>
                            <td><strong><?= e($c['name']) ?></strong><br><small class="muted"><?= e($c['category']) ?><?= $c['address'] ? ' · ' . e($c['address']) : '' ?></small></td>
                            <td class="nowrap"><?= e($c['phone']) ?></td>
                            <td><?= $c['division'] ? e(($c['area'] ? $c['area'] . ', ' : 'All of ') . $c['division']) : 'Nationwide' ?></td>
                            <td class="actions">
                                <button class="btn btn-outline btn-sm" data-open="editModal" data-fill-contact-id="<?= $c['id'] ?>" data-fill-name="<?= e($c['name']) ?>"
                                        data-fill-category="<?= e($c['category']) ?>" data-fill-phone="<?= e($c['phone']) ?>" data-fill-address="<?= e($c['address']) ?>"
                                        data-fill-division="<?= e($c['division']) ?>" data-fill-area="<?= e($c['area']) ?>"><i class="fa-solid fa-pen"></i></button>
                                <form method="post" class="inline-form" data-confirm="Delete <?= e($c['name']) ?>?"><?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="contact_id" value="<?= $c['id'] ?>">
                                    <button class="btn btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$contacts): ?><tr><td colspan="4"><div class="empty"><p>No contacts.</p></div></td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <h3 class="card-title"><i class="fa-solid fa-plus"></i> Add a contact</h3>
        <?php if ($errors): ?><div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= implode('<br>', array_map('e', $errors)) ?></span></div><?php endif; ?>
        <form method="post" class="stack mt-2">
            <?= csrf_field() ?><input type="hidden" name="action" value="create">
            <?php contact_fields('n_'); ?>
            <button class="btn btn-primary">Add contact</button>
        </form>
    </div>
</div>

<dialog class="modal" id="editModal">
    <form method="post">
        <div class="modal-head"><h3>Edit contact</h3><button type="button" class="modal-close" data-close>&times;</button></div>
        <div class="modal-body stack">
            <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="contact_id" data-field="contactId">
            <?php contact_fields('e_'); ?>
            <div class="form-actions"><button type="button" class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary">Save</button></div>
        </div>
    </form>
</dialog>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
