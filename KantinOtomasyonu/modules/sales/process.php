<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_roles(['admin', 'kasiyer']);

if (!is_post()) {
    redirect('modules/sales/index.php');
}
verify_csrf();

$cartJson = (string) ($_POST['cart_json'] ?? '');
$cart = json_decode($cartJson, true);
if (!is_array($cart) || $cart === []) {
    set_flash('error', 'Sepet boş. Satış oluşturulamadı.');
    redirect('modules/sales/index.php');
}

$userId = (int) current_user()['id'];
$saleNo = 'S' . date('YmdHis') . random_int(10, 99);

db()->beginTransaction();
try {
    $totalAmount = 0.0;
    $resolvedItems = [];

    foreach ($cart as $item) {
        $productId = (int) ($item['product_id'] ?? 0);
        $qty = (float) ($item['quantity'] ?? 0);
        if ($productId <= 0 || $qty <= 0) {
            throw new RuntimeException('Sepet verisi hatalı.');
        }

        $product = fetch_one('SELECT id, product_name, sale_price, stock_quantity FROM products WHERE id=:id FOR UPDATE', ['id' => $productId]);
        if (!$product) {
            throw new RuntimeException('Ürün bulunamadı.');
        }
        if ((float) $product['stock_quantity'] < $qty) {
            throw new RuntimeException((string) $product['product_name'] . ' için yeterli stok yok.');
        }

        $unitPrice = (float) $product['sale_price'];
        $lineTotal = $unitPrice * $qty;
        $totalAmount += $lineTotal;

        $resolvedItems[] = [
            'product_id' => $productId,
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
        ];
    }

    execute_query(
        'INSERT INTO sales (sale_no, user_id, total_amount, status, sale_date, created_at)
         VALUES (:sale_no,:user_id,:total_amount,"tamamlandı",NOW(),NOW())',
        ['sale_no' => $saleNo, 'user_id' => $userId, 'total_amount' => $totalAmount]
    );
    $saleId = (int) db()->lastInsertId();

    foreach ($resolvedItems as $line) {
        execute_query(
            'INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, line_total)
             VALUES (:sale_id,:product_id,:quantity,:unit_price,:line_total)',
            $line + ['sale_id' => $saleId]
        );

        execute_query(
            'UPDATE products SET stock_quantity = stock_quantity - :qty WHERE id = :id',
            ['qty' => $line['quantity'], 'id' => $line['product_id']]
        );

        execute_query(
            'INSERT INTO stock_movements (product_id,movement_type,quantity,note,user_id,created_at)
             VALUES (:product_id,"out",:qty,:note,:user_id,NOW())',
            [
                'product_id' => $line['product_id'],
                'qty' => $line['quantity'],
                'note' => 'Satış No: ' . $saleNo,
                'user_id' => $userId,
            ]
        );
    }

    db()->commit();
    set_flash('success', 'Satış başarıyla tamamlandı. No: ' . $saleNo);
} catch (Throwable $e) {
    db()->rollBack();
    set_flash('error', 'Satış tamamlanamadı: ' . $e->getMessage());
}

redirect('modules/sales/index.php');
