<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

if (!is_post()) {
    redirect('modules/products/index.php');
}
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    $used = count_value('SELECT COUNT(*) FROM sale_items WHERE product_id = :id', ['id' => $id]);
    if ($used > 0) {
        set_flash('error', 'Satış geçmişi olan ürün silinemez. Pasif yapabilirsiniz.');
    } else {
        execute_query('DELETE FROM products WHERE id = :id', ['id' => $id]);
        set_flash('success', 'Ürün silindi.');
    }
}
redirect('modules/products/index.php');
