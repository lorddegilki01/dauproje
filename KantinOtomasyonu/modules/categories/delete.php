<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

if (!is_post()) {
    redirect('modules/categories/index.php');
}
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    $productCount = count_value('SELECT COUNT(*) FROM products WHERE category_id = :id', ['id' => $id]);
    if ($productCount > 0) {
        set_flash('error', 'Bu kategoriye bağlı ürünler var. Önce ürünleri taşıyın.');
        redirect('modules/categories/index.php');
    }
    execute_query('DELETE FROM categories WHERE id = :id', ['id' => $id]);
    set_flash('success', 'Kategori silindi.');
}
redirect('modules/categories/index.php');
