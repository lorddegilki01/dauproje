<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
$activeMenu = 'contact';
$pageTitle = 'İletişim';
$errors = [];

if (is_post()) {
    verify_csrf();
    $fullName = normalize_text((string) ($_POST['full_name'] ?? ''));
    $email = mb_strtolower(normalize_text((string) ($_POST['email'] ?? '')), 'UTF-8');
    $subject = normalize_text((string) ($_POST['subject'] ?? ''));
    $message = normalize_text((string) ($_POST['message'] ?? ''));

    if ($fullName === '' || $email === '' || $subject === '' || $message === '') {
        $errors[] = 'Tüm alanlar zorunludur.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi girin.';
    }

    if (!$errors) {
        execute_query(
            'INSERT INTO contacts (full_name, email, subject, message, status, created_at)
             VALUES (:full_name, :email, :subject, :message, "yeni", NOW())',
            [
                'full_name' => $fullName,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
            ]
        );
        set_flash('success', 'Mesajınız alındı. En kısa sürede dönüş yapılacaktır.');
        redirect('pages/contact.php');
    }
}

require __DIR__ . '/../includes/header.php';
?>
<section class="card form-card">
    <h1>İletişim</h1>
    <p>Öneri, destek veya iş birliği taleplerinizi bize iletebilirsiniz.</p>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" class="form grid-2">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Ad Soyad<input type="text" name="full_name" required value="<?= e((string) ($_POST['full_name'] ?? '')) ?>"></label>
        <label>E-posta<input type="email" name="email" required value="<?= e((string) ($_POST['email'] ?? '')) ?>"></label>
        <label class="full">Konu<input type="text" name="subject" required value="<?= e((string) ($_POST['subject'] ?? '')) ?>"></label>
        <label class="full">Mesajınız<textarea name="message" rows="5" required><?= e((string) ($_POST['message'] ?? '')) ?></textarea></label>
        <div class="full actions"><button class="btn primary" type="submit">Mesaj Gönder</button></div>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

