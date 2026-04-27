<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

if (!is_post()) {
    redirect('modules/sales/index.php');
}
verify_csrf();
$saleId = (int) ($_POST['id'] ?? 0);

$sale = fetch_one('SELECT * FROM sales WHERE id = :id', ['id' => $saleId]);
if (!$sale || !in_array((string) $sale['status'], ['tamamlandı'], true)) {
    set_flash('error', 'Satış iptal edilemedi.');
    redirect('modules/sales/index.php');
}

db()->beginTransaction();
try {
    $items = fetch_all('SELECT * FROM sale_items WHERE sale_id = :id', ['id' => $saleId]);
    foreach ($items as $item) {
        execute_query('UPDATE products SET stock_quantity = stock_quantity + :qty WHERE id = :id', [
            'qty' => $item['quantity'],
            'id' => $item['product_id'],
        ]);
        execute_query(
            'INSERT INTO stock_movements (product_id,movement_type,quantity,note,user_id,created_at)
             VALUES (:product_id,"in",:quantity,:note,:user_id,NOW())',
            [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'note' => 'Satış iptali: ' . $sale['sale_no'],
                'user_id' => (int) current_user()['id'],
            ]
        );
    }
    execute_query('UPDATE sales SET status = "iptal" WHERE id = :id', ['id' => $saleId]);
    db()->commit();
    set_flash('success', 'Satış iptal edildi ve stok geri yüklendi.');
} catch (Throwable $e) {
    db()->rollBack();
    set_flash('error', 'Satış iptali başarısız: ' . $e->getMessage());
}

redirect('modules/sales/index.php');
