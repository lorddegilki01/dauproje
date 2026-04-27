<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$book = fetch_one('SELECT id, donor_user_id, title FROM books WHERE id = :id', ['id' => $id]);

if (!$book) {
    set_flash('error', 'Kitap kaydı bulunamadı.');
    redirect('books/my_books.php');
}

if (!is_admin() && (int) $book['donor_user_id'] !== (int) current_user()['id']) {
    set_flash('error', 'Bu kayıt için silme yetkiniz yok.');
    redirect('books/my_books.php');
}

execute_query('DELETE FROM books WHERE id = :id', ['id' => $id]);
log_activity((int) current_user()['id'], 'Kitap', 'Kitap silindi', (string) $book['title']);
set_flash('success', 'Kitap kaydı silindi.');
redirect('books/my_books.php');

