<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    $_SESSION = [];
    session_destroy();
    session_start();
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Oturum süreniz doldu. Lütfen tekrar giriş yapın.'];
}
$_SESSION['last_activity'] = time();

function app_url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . app_url($path));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
        http_response_code(419);
        exit('CSRF doğrulaması başarısız.');
    }
}

function fetch_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_one(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function execute_query(string $sql, array $params = []): bool
{
    $stmt = db()->prepare($sql);
    return $stmt->execute($params);
}

function count_value(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function sum_value(string $sql, array $params = []): float
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (float) ($stmt->fetchColumn() ?: 0);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user && ($user['role'] ?? '') === 'admin';
}

function is_kasiyer(): bool
{
    $user = current_user();
    return $user && ($user['role'] ?? '') === 'kasiyer';
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Bu sayfaya erişmek için giriş yapmalısınız.');
        redirect('auth/login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        set_flash('error', 'Bu işlem için yönetici yetkisi gereklidir.');
        redirect('index.php');
    }
}

function require_roles(array $roles): void
{
    require_login();
    $role = (string) (current_user()['role'] ?? '');
    if (!in_array($role, $roles, true)) {
        set_flash('error', 'Bu sayfaya erişim yetkiniz yok.');
        redirect('index.php');
    }
}

function logout_user(): void
{
    $_SESSION = [];
    session_destroy();
}

function format_money(float $value): string
{
    return number_format($value, 2, ',', '.') . ' ₺';
}

function format_date(?string $date, string $format = 'd.m.Y H:i'): string
{
    if (!$date) {
        return '-';
    }
    return date($format, strtotime($date));
}

function badge_status(string $status): string
{
    return match ($status) {
        'aktif', 'tamamlandı', 'başarılı' => 'badge success',
        'pasif', 'iptal', 'başarısız' => 'badge danger',
        'kritik', 'stok bitti', 'işleniyor' => 'badge warning',
        'bilgi' => 'badge neutral',
        default => 'badge neutral',
    };
}

function status_label(string $status): string
{
    return $status;
}

function active_menu(string $key): string
{
    global $activeMenu;
    return ($activeMenu ?? '') === $key ? 'active' : '';
}

function ensure_positive_number(string $value): float
{
    $normalized = str_replace(',', '.', trim($value));
    $number = is_numeric($normalized) ? (float) $normalized : 0.0;
    return max(0, $number);
}

function update_session_user(array $userRow): void
{
    $_SESSION['user'] = [
        'id' => (int) $userRow['id'],
        'full_name' => (string) $userRow['full_name'],
        'username' => (string) $userRow['username'],
        'role' => (string) $userRow['role'],
    ];
}

function update_product_stock(int $productId, float $quantity, string $movementType, ?string $note, int $userId): void
{
    db()->beginTransaction();
    try {
        $product = fetch_one('SELECT id, stock_quantity FROM products WHERE id = :id FOR UPDATE', ['id' => $productId]);
        if (!$product) {
            throw new RuntimeException('Ürün bulunamadı.');
        }

        $currentStock = (float) $product['stock_quantity'];
        $newStock = $movementType === 'out' ? $currentStock - $quantity : $currentStock + $quantity;
        if ($newStock < 0) {
            throw new RuntimeException('Stok eksiye düşemez.');
        }

        execute_query(
            'UPDATE products SET stock_quantity = :stock WHERE id = :id',
            ['stock' => $newStock, 'id' => $productId]
        );

        execute_query(
            'INSERT INTO stock_movements (product_id, movement_type, quantity, note, user_id, created_at)
             VALUES (:product_id, :movement_type, :quantity, :note, :user_id, NOW())',
            [
                'product_id' => $productId,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'note' => $note,
                'user_id' => $userId,
            ]
        );
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }
}
