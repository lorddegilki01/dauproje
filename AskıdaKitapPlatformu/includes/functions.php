<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    $_SESSION = [];
    session_destroy();
    session_start();
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Oturum süresi doldu. Lütfen tekrar giriş yapın.'];
}
$_SESSION['last_activity'] = time();

const ROLE_ADMIN = 'admin';
const ROLE_USER = 'kullanici';
const ROLE_DONOR = 'bagisci';
const ROLE_REQUESTER = 'talep_sahibi';

function role_labels(): array
{
    return [
        ROLE_ADMIN => 'Yönetici',
        ROLE_USER => 'Kullanıcı',
        ROLE_DONOR => 'Bağışçı',
        ROLE_REQUESTER => 'Talep Sahibi',
    ];
}

function role_label(string $role): string
{
    return role_labels()[$role] ?? $role;
}

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

function has_role(string|array $roles): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    $roleList = is_array($roles) ? $roles : [$roles];
    return in_array((string) ($user['role'] ?? ''), $roleList, true);
}

function is_admin(): bool
{
    return has_role(ROLE_ADMIN);
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Devam etmek için giriş yapmalısınız.');
        redirect('auth/login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        set_flash('error', 'Bu alan sadece yöneticiye açıktır.');
        redirect('index.php');
    }
}

function require_role(array $roles): void
{
    require_login();
    if (!has_role($roles)) {
        set_flash('error', 'Bu alan için yetkiniz bulunmuyor.');
        redirect('index.php');
    }
}

function normalize_text(string $value): string
{
    return trim((string) preg_replace('/\s+/u', ' ', $value));
}

function format_date(?string $date, string $format = 'd.m.Y H:i'): string
{
    if (!$date) {
        return '-';
    }
    return date($format, strtotime($date));
}

function badge_class(string $status): string
{
    return match ($status) {
        'askıda', 'askida', 'bekliyor', 'onaylandı', 'onaylandi', 'aktif', 'talep edildi', 'eşleşti', 'eslesti', 'bilgi' => 'badge warning',
        'teslim edildi', 'tamamlandı', 'başarı' => 'badge success',
        'reddedildi', 'pasif', 'iptal', 'iptal edildi', 'hata' => 'badge danger',
        default => 'badge neutral',
    };
}

function can_manage_request(array $request): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    return ((int) ($request['donor_user_id'] ?? 0) === (int) $user['id']) || (($user['role'] ?? '') === ROLE_ADMIN);
}

function save_uploaded_cover(string $fieldName = 'cover_image'): ?string
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmp = (string) $_FILES[$fieldName]['tmp_name'];
    $name = (string) $_FILES[$fieldName]['name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return null;
    }

    $fileName = 'book_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetDir = __DIR__ . '/../assets/uploads';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }
    $target = $targetDir . '/' . $fileName;
    if (!move_uploaded_file($tmp, $target)) {
        return null;
    }

    return 'assets/uploads/' . $fileName;
}

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function client_agent(): string
{
    return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 500);
}

function log_activity(?int $userId, string $module, string $action, string $details = ''): void
{
    execute_query(
        'INSERT INTO activity_logs (user_id, module_name, action_name, details, ip_address, created_at)
         VALUES (:user_id, :module_name, :action_name, :details, :ip_address, NOW())',
        [
            'user_id' => $userId,
            'module_name' => $module,
            'action_name' => $action,
            'details' => $details,
            'ip_address' => client_ip(),
        ]
    );
}

function system_log(?int $userId, string $eventType, string $eventStatus, string $message): void
{
    execute_query(
        'INSERT INTO system_logs (user_id, event_type, event_status, message, ip_address, user_agent, created_at)
         VALUES (:user_id, :event_type, :event_status, :message, :ip_address, :user_agent, NOW())',
        [
            'user_id' => $userId,
            'event_type' => $eventType,
            'event_status' => $eventStatus,
            'message' => $message,
            'ip_address' => client_ip(),
            'user_agent' => client_agent(),
        ]
    );
}

function create_notification(?int $userId, string $title, string $message, string $type = 'bilgi', string $url = '#'): void
{
    execute_query(
        'INSERT INTO notifications (user_id, title, message, notification_type, target_url, is_read, created_at)
         VALUES (:user_id, :title, :message, :notification_type, :target_url, 0, NOW())',
        [
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'notification_type' => $type,
            'target_url' => $url,
        ]
    );
}

function request_status_label(string $status): string
{
    return match ($status) {
        'bekliyor' => 'Bekliyor',
        'onaylandı', 'onaylandi' => 'Onaylandı',
        'reddedildi' => 'Reddedildi',
        'iptal' => 'İptal',
        default => $status,
    };
}

function book_status_label(string $status): string
{
    return match ($status) {
        'askıda', 'askida' => 'Askıda',
        'talep edildi' => 'Talep Edildi',
        'eşleşti', 'eslesti' => 'Eşleşti',
        'teslim edildi' => 'Teslim Edildi',
        'pasif' => 'Pasif',
        default => $status,
    };
}

function rate_limit_key(string $key): string
{
    return 'rate_limit_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
}

function check_rate_limit(string $key, int $limit, int $windowSeconds): bool
{
    $sessionKey = rate_limit_key($key);
    $now = time();
    $bucket = $_SESSION[$sessionKey] ?? ['count' => 0, 'start' => $now];

    if (($now - (int) $bucket['start']) > $windowSeconds) {
        $bucket = ['count' => 0, 'start' => $now];
    }

    $bucket['count'] = (int) $bucket['count'] + 1;
    $_SESSION[$sessionKey] = $bucket;

    return (int) $bucket['count'] <= $limit;
}

function unread_notification_count(int $userId): int
{
    return count_value(
        'SELECT COUNT(*)
         FROM notifications
         WHERE is_read = 0 AND (user_id IS NULL OR user_id = :user_id)',
        ['user_id' => $userId]
    );
}

function update_session_user(array $userRow): void
{
    $_SESSION['user'] = [
        'id' => (int) $userRow['id'],
        'full_name' => (string) $userRow['full_name'],
        'username' => (string) $userRow['username'],
        'email' => (string) $userRow['email'],
        'role' => (string) $userRow['role'],
        'city' => (string) ($userRow['city'] ?? ''),
    ];
}
