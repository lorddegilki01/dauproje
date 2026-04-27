<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$activeMenu = 'expenses';
$pageTitle = 'Gider Yönetimi';
$errors = [];

if (is_post() && !isset($_POST['delete_id'])) {
    verify_csrf();
    $type = trim((string) ($_POST['expense_type'] ?? ''));
    $amount = ensure_positive_number((string) ($_POST['amount'] ?? '0'));
    $date = trim((string) ($_POST['expense_date'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($type === '' || $amount <= 0 || $date === '') {
        $errors[] = 'Gider türü, tutar ve tarih zorunludur.';
    }

    if (!$errors) {
        execute_query(
            'INSERT INTO expenses (expense_type, amount, expense_date, description, created_at)
             VALUES (:expense_type,:amount,:expense_date,:description,NOW())',
            ['expense_type' => $type, 'amount' => $amount, 'expense_date' => $date, 'description' => $description]
        );
        set_flash('success', 'Gider eklendi.');
        redirect('modules/expenses/index.php');
    }
}

if (is_post() && isset($_POST['delete_id'])) {
    verify_csrf();
    execute_query('DELETE FROM expenses WHERE id = :id', ['id' => (int) $_POST['delete_id']]);
    set_flash('success', 'Gider silindi.');
    redirect('modules/expenses/index.php');
}

$rows = fetch_all('SELECT * FROM expenses ORDER BY expense_date DESC, id DESC');
$monthlyExpense = sum_value('SELECT SUM(amount) FROM expenses WHERE YEAR(expense_date)=YEAR(CURDATE()) AND MONTH(expense_date)=MONTH(CURDATE())');

require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <h3>Yeni Gider</h3>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" class="form grid-2">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Gider Türü
            <select name="expense_type" required>
                <?php foreach (['Elektrik', 'Su', 'Malzeme', 'Personel', 'Diğer'] as $type): ?>
                    <option value="<?= e($type) ?>"><?= e($type) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Tutar<input type="text" name="amount" required></label>
        <label>Tarih<input type="date" name="expense_date" value="<?= e(date('Y-m-d')) ?>" required></label>
        <label>Açıklama<input type="text" name="description"></label>
        <div class="full actions"><button class="btn primary" type="submit">Kaydet</button></div>
    </form>
</section>

<section class="card">
    <h3>Aylık Gider Toplamı: <?= e(format_money($monthlyExpense)) ?></h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Tarih</th><th>Tür</th><th>Tutar</th><th>Açıklama</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e(format_date((string) $row['expense_date'], 'd.m.Y')) ?></td>
                    <td><?= e((string) $row['expense_type']) ?></td>
                    <td><?= e(format_money((float) $row['amount'])) ?></td>
                    <td><?= e((string) $row['description']) ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="delete_id" value="<?= e((string) $row['id']) ?>">
                            <button class="btn-link danger" type="submit" data-confirm="Gider silinsin mi?">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

