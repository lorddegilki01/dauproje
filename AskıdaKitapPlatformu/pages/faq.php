<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
$activeMenu = 'faq';
$pageTitle = 'Sık Sorulan Sorular';
require __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <h1>Sık Sorulan Sorular</h1>
    <div class="faq-list">
        <article>
            <h3>Kitap bağışı nasıl yapılır?</h3>
            <p>Üye olduktan sonra “Bağışlarım > Yeni Kitap Ekle” adımıyla kitabınızı askıya bırakabilirsiniz.</p>
        </article>
        <article>
            <h3>Bir kitap için birden fazla talep olabilir mi?</h3>
            <p>Evet. Bağışçı veya admin uygun talebi onaylar, diğer bekleyen talepler otomatik güncellenir.</p>
        </article>
        <article>
            <h3>Teslim süreci nasıl takip edilir?</h3>
            <p>“Teslim Süreci” sayfasında eşleşme ve teslim durumu gerçek zamanlı olarak görüntülenir.</p>
        </article>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

