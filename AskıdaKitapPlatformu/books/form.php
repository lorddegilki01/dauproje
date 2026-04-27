<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();

$activeMenu = 'my_books';
$bookId = (int) ($_GET['id'] ?? 0);
$editing = $bookId > 0;
$pageTitle = $editing ? 'Kitap Düzenle' : 'Kitap Bağışla';
$errors = [];
$user = current_user();

$book = [
    'title' => '',
    'author' => '',
    'category_id' => '',
    'description' => '',
    'delivery_type' => 'elden',
    'city' => (string) ($user['city'] ?? ''),
    'contact_preference' => 'platform_mesaji',
    'status' => 'askıda',
    'cover_image' => '',
];

if ($editing) {
    $row = fetch_one('SELECT * FROM books WHERE id = :id', ['id' => $bookId]);
    if (!$row) {
        set_flash('error', 'Kitap kaydı bulunamadı.');
        redirect('books/my_books.php');
    }
    if (!is_admin() && (int) $row['donor_user_id'] !== (int) $user['id']) {
        set_flash('error', 'Bu kitap kaydını düzenleme yetkiniz yok.');
        redirect('books/my_books.php');
    }
    $book = $row;
}

if (is_post()) {
    verify_csrf();

    $title = normalize_text((string) ($_POST['title'] ?? ''));
    $author = normalize_text((string) ($_POST['author'] ?? ''));
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $description = normalize_text((string) ($_POST['description'] ?? ''));
    $city = normalize_text((string) ($_POST['city'] ?? ''));
    $deliveryType = (string) ($_POST['delivery_type'] ?? 'elden');
    $contactPreference = (string) ($_POST['contact_preference'] ?? 'platform_mesaji');
    $status = (string) ($_POST['status'] ?? 'askıda');

    if ($title === '' || $author === '' || $description === '' || $city === '') {
        $errors[] = 'Kitap adı, yazar, açıklama ve şehir alanları zorunludur.';
    }
    if (!in_array($deliveryType, ['elden', 'kargo', 'farketmez'], true)) {
        $errors[] = 'Teslim şekli geçersiz.';
    }
    if (!in_array($contactPreference, ['platform_mesaji', 'telefon', 'eposta'], true)) {
        $errors[] = 'İletişim tercihi geçersiz.';
    }
    if (!in_array($status, ['askıda', 'talep edildi', 'teslim edildi', 'pasif'], true)) {
        $status = 'askıda';
    }

    $coverImage = save_uploaded_cover();
    if ($coverImage === null && $editing) {
        $coverImage = (string) $book['cover_image'];
    }

    if (!$errors) {
        if ($editing) {
            execute_query(
                'UPDATE books
                 SET title=:title, author=:author, category_id=:category_id, description=:description, city=:city,
                     delivery_type=:delivery_type, contact_preference=:contact_preference, status=:status,
                     cover_image=:cover_image, updated_at=NOW()
                 WHERE id=:id',
                [
                    'title' => $title,
                    'author' => $author,
                    'category_id' => $categoryId ?: null,
                    'description' => $description,
                    'city' => $city,
                    'delivery_type' => $deliveryType,
                    'contact_preference' => $contactPreference,
                    'status' => $status,
                    'cover_image' => $coverImage ?: null,
                    'id' => $bookId,
                ]
            );
            log_activity((int) $user['id'], 'Kitap', 'Kitap düzenlendi', $title);
            set_flash('success', 'Kitap bilgileri güncellendi.');
        } else {
            execute_query(
                'INSERT INTO books
                 (donor_user_id, category_id, title, author, description, city, delivery_type, contact_preference, cover_image, status, is_active, created_at, updated_at)
                 VALUES (:donor_user_id, :category_id, :title, :author, :description, :city, :delivery_type, :contact_preference, :cover_image, :status, 1, NOW(), NOW())',
                [
                    'donor_user_id' => (int) $user['id'],
                    'category_id' => $categoryId ?: null,
                    'title' => $title,
                    'author' => $author,
                    'description' => $description,
                    'city' => $city,
                    'delivery_type' => $deliveryType,
                    'contact_preference' => $contactPreference,
                    'cover_image' => $coverImage ?: null,
                    'status' => $status,
                ]
            );
            log_activity((int) $user['id'], 'Kitap', 'Kitap eklendi', $title);
            create_notification(null, 'Yeni askıdaki kitap', $title . ' sisteme eklendi.', 'bilgi', app_url('books/index.php'));
            set_flash('success', 'Kitap başarıyla askıya eklendi.');
        }

        redirect('books/my_books.php');
    }
}

$categories = fetch_all('SELECT id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name');
require __DIR__ . '/../includes/header.php';
?>
<section class="card form-card">
    <h2><?= e($pageTitle) ?></h2>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data" class="form grid-2">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <label>Kitap Adı
            <input type="text" name="title" required value="<?= e((string) ($_POST['title'] ?? $book['title'])) ?>">
        </label>
        <label>Yazar
            <input type="text" name="author" required value="<?= e((string) ($_POST['author'] ?? $book['author'])) ?>">
        </label>
        <label>Kategori
            <select name="category_id">
                <option value="0">Kategori seçiniz</option>
                <?php
                $currentCategory = (int) ($_POST['category_id'] ?? $book['category_id'] ?? 0);
                foreach ($categories as $category):
                ?>
                    <option value="<?= e((string) $category['id']) ?>" <?= $currentCategory === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= e((string) $category['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Şehir
            <input type="text" name="city" required value="<?= e((string) ($_POST['city'] ?? $book['city'])) ?>">
        </label>
        <label>Teslim Şekli
            <select name="delivery_type">
                <?php
                $currentDelivery = (string) ($_POST['delivery_type'] ?? $book['delivery_type']);
                foreach (['elden' => 'Elden', 'kargo' => 'Kargo', 'farketmez' => 'Fark Etmez'] as $key => $label):
                ?>
                    <option value="<?= e($key) ?>" <?= $currentDelivery === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>İletişim Tercihi
            <select name="contact_preference">
                <?php
                $currentContact = (string) ($_POST['contact_preference'] ?? $book['contact_preference']);
                foreach (['platform_mesaji' => 'Platform Mesajı', 'telefon' => 'Telefon', 'eposta' => 'E-posta'] as $key => $label):
                ?>
                    <option value="<?= e($key) ?>" <?= $currentContact === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <?php if (is_admin()): ?>
            <label>Durum
                <select name="status">
                    <?php
                    $currentStatus = (string) ($_POST['status'] ?? $book['status']);
                    foreach (['askıda', 'talep edildi', 'teslim edildi', 'pasif'] as $status):
                    ?>
                        <option value="<?= e($status) ?>" <?= $currentStatus === $status ? 'selected' : '' ?>>
                            <?= e(book_status_label($status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>

        <label class="full">Açıklama
            <textarea name="description" rows="4" required><?= e((string) ($_POST['description'] ?? $book['description'])) ?></textarea>
        </label>
        <label class="full">Kitap Görseli (Opsiyonel)
            <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp">
        </label>

        <div class="full actions">
            <button class="btn primary" type="submit"><?= $editing ? 'Güncelle' : 'Askıya Ekle' ?></button>
            <a class="btn ghost" href="<?= e(app_url('books/my_books.php')) ?>">Vazgeç</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

