<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/mailer.php';

if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    $_SESSION = [];
    session_destroy();
    session_start();
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Oturum süresi dolduğu için yeniden giriş yapmalısınız.'];
}
$_SESSION['last_activity'] = time();

function app_url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function app_full_url(string $path = ''): string
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . app_url($path);
}

function e(null|string|int|float $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . app_url($path));
    exit;
}

function role_home_path(string $role): string
{
    return $role === 'admin' ? 'index.php' : 'personnel/dashboard.php';
}

function redirect_home_by_role(): void
{
    $user = current_user();
    if (!$user) {
        redirect('auth/login.php');
    }
    redirect(role_home_path((string) $user['role']));
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
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        include __DIR__ . '/../errors/419.php';
        exit;
    }
}

function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
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
    if ($user === null) {
        return false;
    }

    $role = mb_strtolower(trim((string) ($user['role'] ?? '')), 'UTF-8');
    return $role === 'admin';
}

function is_personnel(): bool
{
    $user = current_user();
    if ($user === null) {
        return false;
    }

    $role = mb_strtolower(trim((string) ($user['role'] ?? '')), 'UTF-8');
    return $role === 'personel' || $role === 'personnel';
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Devam etmek için giriş yapmalısınız.');
        redirect('auth/login.php');
    }
}

function require_role(array $roles): void
{
    require_login();
    $user = current_user();
    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        include __DIR__ . '/../errors/403.php';
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (is_admin()) {
        return;
    }
    if (is_personnel()) {
        set_flash('warning', 'Bu alan yalnızca yönetici kullanıcılar içindir.');
        redirect('personnel/dashboard.php');
    }
    http_response_code(403);
    include __DIR__ . '/../errors/403.php';
    exit;
}

function require_personnel(): void
{
    require_login();
    if (is_personnel()) {
        return;
    }
    if (is_admin()) {
        set_flash('warning', 'Bu alan personel kullanıcı paneline özeldir.');
        redirect('index.php');
    }
    http_response_code(403);
    include __DIR__ . '/../errors/403.php';
    exit;
}

function current_personnel_record(): ?array
{
    if (!is_personnel()) {
        return null;
    }

    $user = current_user();
    if (!$user) {
        return null;
    }

    if (column_exists('personnel', 'user_id')) {
        return fetch_one(
            'SELECT * FROM personnel WHERE user_id = :user_id LIMIT 1',
            ['user_id' => (int) $user['id']]
        );
    }

    $userRow = fetch_one(
        'SELECT id, full_name, email FROM users WHERE id = :id LIMIT 1',
        ['id' => (int) $user['id']]
    );

    if (!$userRow) {
        return null;
    }

    if (column_exists('personnel', 'email') && !empty($userRow['email'])) {
        $personnelByEmail = fetch_one(
            'SELECT * FROM personnel WHERE email = :email LIMIT 1',
            ['email' => (string) $userRow['email']]
        );
        if ($personnelByEmail) {
            return $personnelByEmail;
        }
    }

    return fetch_one(
        'SELECT * FROM personnel WHERE full_name = :full_name LIMIT 1',
        ['full_name' => (string) $userRow['full_name']]
    );
}

function require_personnel_profile(): array
{
    require_personnel();
    $personnel = current_personnel_record();
    if (!$personnel) {
        http_response_code(403);
        echo 'Personel kaydınız bulunamadı. Lütfen yönetici ile iletişime geçin.';
        exit;
    }
    return $personnel;
}

function format_date(?string $date, string $format = 'd.m.Y'): string
{
    if (!$date) {
        return '-';
    }
    return date($format, strtotime($date));
}

function format_money(float $amount): string
{
    return number_format($amount, 2, ',', '.') . ' TL';
}

function badge_class(string $status): string
{
    return match ($status) {
        'müsait', 'aktif', 'tamamlandı', 'onaylandı', 'çözüldü', 'iade edildi' => 'badge success',
        'kullanımda', 'planlandı', 'yaklaşıyor', 'bekliyor', 'inceleniyor' => 'badge warning',
        'bakımda', 'pasif', 'gecikti', 'arıza', 'iade edilmedi', 'reddedildi', 'kritik', 'açık' => 'badge danger',
        default => 'badge neutral',
    };
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

function table_exists(string $tableName): bool
{
    $row = fetch_one(
        'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name LIMIT 1',
        ['table_name' => $tableName]
    );
    return $row !== null;
}

function column_exists(string $tableName, string $columnName): bool
{
    $row = fetch_one(
        'SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name
         LIMIT 1',
        [
            'table_name' => $tableName,
            'column_name' => $columnName,
        ]
    );
    return $row !== null;
}

function password_reset_schema_ready(): bool
{
    return column_exists('users', 'email') && table_exists('password_resets');
}

function log_activity(string $action, string $module, string $details): void
{
    if (!is_logged_in()) {
        return;
    }

    execute_query(
        'INSERT INTO activity_logs (user_id, module_name, action_name, details, ip_address, created_at)
         VALUES (:user_id, :module_name, :action_name, :details, :ip_address, NOW())',
        [
            'user_id' => (int) current_user()['id'],
            'module_name' => $module,
            'action_name' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]
    );
}

function normalize_datetime_local(string $value): string
{
    return date('Y-m-d H:i:s', strtotime($value));
}

function ensure_assignment_consistency(int $vehicleId): void
{
    $activeAssignment = fetch_one(
        "SELECT id FROM vehicle_assignments WHERE vehicle_id = :vehicle_id AND return_status = 'iade edilmedi' LIMIT 1",
        ['vehicle_id' => $vehicleId]
    );

    $maintenanceDue = fetch_one(
        'SELECT id FROM maintenance_records WHERE vehicle_id = :vehicle_id AND next_maintenance_date < CURDATE() LIMIT 1',
        ['vehicle_id' => $vehicleId]
    );

    if ($activeAssignment) {
        execute_query("UPDATE vehicles SET status = 'kullanımda' WHERE id = :id", ['id' => $vehicleId]);
        return;
    }

    if ($maintenanceDue) {
        execute_query("UPDATE vehicles SET status = 'bakımda' WHERE id = :id", ['id' => $vehicleId]);
        return;
    }

    execute_query("UPDATE vehicles SET status = 'müsait' WHERE id = :id", ['id' => $vehicleId]);
}

function notifications_schema_ready(): bool
{
    return table_exists('notifications')
        && column_exists('notifications', 'is_read')
        && column_exists('notifications', 'unique_key');
}

function relative_time_tr(string $datetime): string
{
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return '-';
    }

    $diff = time() - $timestamp;
    if ($diff < 60) {
        return 'az önce';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' dakika önce';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' saat önce';
    }
    if ($diff < 604800) {
        return floor($diff / 86400) . ' gün önce';
    }

    return format_date($datetime, 'd.m.Y H:i');
}

function upsert_notification(array $data): void
{
    if (!notifications_schema_ready()) {
        return;
    }

    execute_query(
        'INSERT INTO notifications
            (user_id, type, title, message, related_module, related_record_id, url, icon, color_class, unique_key, is_read, created_at)
         VALUES
            (:user_id, :type, :title, :message, :related_module, :related_record_id, :url, :icon, :color_class, :unique_key, 0, NOW())
         ON DUPLICATE KEY UPDATE
            type = VALUES(type),
            title = VALUES(title),
            message = VALUES(message),
            related_module = VALUES(related_module),
            related_record_id = VALUES(related_record_id),
            url = VALUES(url),
            icon = VALUES(icon),
            color_class = VALUES(color_class),
            updated_at = NOW()',
        [
            'user_id' => (int) $data['user_id'],
            'type' => (string) $data['type'],
            'title' => (string) $data['title'],
            'message' => (string) $data['message'],
            'related_module' => $data['related_module'] !== null ? (string) $data['related_module'] : null,
            'related_record_id' => $data['related_record_id'] !== null ? (int) $data['related_record_id'] : null,
            'url' => $data['url'] !== null ? (string) $data['url'] : null,
            'icon' => $data['icon'] !== null ? (string) $data['icon'] : null,
            'color_class' => $data['color_class'] !== null ? (string) $data['color_class'] : null,
            'unique_key' => (string) $data['unique_key'],
        ]
    );
}

function sync_admin_notifications(int $userId): void
{
    if (!notifications_schema_ready()) {
        return;
    }

    $maintenanceRows = fetch_all(
        'SELECT mr.id, v.plate_number, mr.next_maintenance_date
         FROM maintenance_records mr
         INNER JOIN vehicles v ON v.id = mr.vehicle_id
         WHERE mr.next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
         ORDER BY mr.next_maintenance_date ASC
         LIMIT 20'
    );
    foreach ($maintenanceRows as $row) {
        $isOverdue = strtotime((string) $row['next_maintenance_date']) < strtotime(date('Y-m-d'));
        upsert_notification([
            'user_id' => $userId,
            'type' => $isOverdue ? 'bakim_gecikme' : 'bakim_yaklasan',
            'title' => $isOverdue ? 'Bakım tarihi geçti' : 'Bakım yaklaşıyor',
            'message' => $row['plate_number'] . ' için bakım tarihi: ' . format_date((string) $row['next_maintenance_date']),
            'related_module' => 'maintenance',
            'related_record_id' => (int) $row['id'],
            'url' => app_url('modules/maintenance/index.php'),
            'icon' => '🛠',
            'color_class' => $isOverdue ? 'danger' : 'warning',
            'unique_key' => 'maintenance_' . (int) $row['id'],
        ]);
    }

    $issueRows = fetch_all(
        "SELECT ir.id, ir.subject, v.plate_number
         FROM issue_reports ir
         INNER JOIN vehicles v ON v.id = ir.vehicle_id
         WHERE ir.status IN ('açık', 'inceleniyor')
         ORDER BY ir.created_at DESC
         LIMIT 20"
    );
    foreach ($issueRows as $row) {
        upsert_notification([
            'user_id' => $userId,
            'type' => 'acik_ariza',
            'title' => 'Açık arıza bildirimi',
            'message' => $row['plate_number'] . ' - ' . $row['subject'],
            'related_module' => 'issues',
            'related_record_id' => (int) $row['id'],
            'url' => app_url('modules/issues/index.php'),
            'icon' => '🚨',
            'color_class' => 'danger',
            'unique_key' => 'issue_' . (int) $row['id'],
        ]);
    }

    $lateRows = fetch_all(
        "SELECT va.id, v.plate_number, va.expected_return_at
         FROM vehicle_assignments va
         INNER JOIN vehicles v ON v.id = va.vehicle_id
         WHERE va.return_status = 'iade edilmedi'
           AND va.expected_return_at IS NOT NULL
           AND va.expected_return_at < NOW()
         ORDER BY va.expected_return_at ASC
         LIMIT 20"
    );
    foreach ($lateRows as $row) {
        upsert_notification([
            'user_id' => $userId,
            'type' => 'geciken_teslim',
            'title' => 'Geciken araç teslimi',
            'message' => $row['plate_number'] . ' planlanan teslim: ' . format_date((string) $row['expected_return_at'], 'd.m.Y H:i'),
            'related_module' => 'assignments',
            'related_record_id' => (int) $row['id'],
            'url' => app_url('modules/assignments/index.php'),
            'icon' => '⏱',
            'color_class' => 'info',
            'unique_key' => 'late_assignment_' . (int) $row['id'],
        ]);
    }

    $requestRows = fetch_all(
        "SELECT vr.id, p.full_name, v.plate_number
         FROM vehicle_requests vr
         INNER JOIN personnel p ON p.id = vr.personnel_id
         INNER JOIN vehicles v ON v.id = vr.vehicle_id
         WHERE vr.status = 'bekliyor'
         ORDER BY vr.request_date DESC
         LIMIT 20"
    );
    foreach ($requestRows as $row) {
        upsert_notification([
            'user_id' => $userId,
            'type' => 'yeni_talep',
            'title' => 'Yeni araç talebi',
            'message' => $row['full_name'] . ' - ' . $row['plate_number'],
            'related_module' => 'requests',
            'related_record_id' => (int) $row['id'],
            'url' => app_url('modules/requests/index.php'),
            'icon' => '📌',
            'color_class' => 'warning',
            'unique_key' => 'request_' . (int) $row['id'],
        ]);
    }

    $announcementRows = fetch_all(
        "SELECT id, title
         FROM announcements
         WHERE is_active = 1 AND target_role IN ('tümü', 'admin')
         ORDER BY created_at DESC
         LIMIT 20"
    );
    foreach ($announcementRows as $row) {
        upsert_notification([
            'user_id' => $userId,
            'type' => 'duyuru',
            'title' => 'Sistem duyurusu',
            'message' => $row['title'],
            'related_module' => 'announcements',
            'related_record_id' => (int) $row['id'],
            'url' => app_url('modules/announcements/index.php'),
            'icon' => '📢',
            'color_class' => 'neutral',
            'unique_key' => 'announcement_' . (int) $row['id'],
        ]);
    }
}

function sync_personnel_notifications(int $userId): void
{
    if (!notifications_schema_ready()) {
        return;
    }

    $personnel = current_personnel_record();
    if (!$personnel) {
        return;
    }

    $personnelId = (int) $personnel['id'];

    $announcementRows = fetch_all(
        "SELECT id, title
         FROM announcements
         WHERE is_active = 1
           AND target_role IN ('tÃ¼mÃ¼', 'tümü', 'personel')
         ORDER BY created_at DESC
         LIMIT 30"
    );
    foreach ($announcementRows as $row) {
        upsert_notification([
            'user_id' => $userId,
            'type' => 'duyuru',
            'title' => 'Sistem duyurusu',
            'message' => (string) $row['title'],
            'related_module' => 'announcements',
            'related_record_id' => (int) $row['id'],
            'url' => app_url('personnel/dashboard.php'),
            'icon' => '📢',
            'color_class' => 'neutral',
            'unique_key' => 'personnel_announcement_' . (int) $row['id'],
        ]);
    }

    $requestRows = fetch_all(
        "SELECT id, status, usage_purpose
         FROM vehicle_requests
         WHERE personnel_id = :personnel_id
         ORDER BY request_date DESC
         LIMIT 30",
        ['personnel_id' => $personnelId]
    );
    foreach ($requestRows as $row) {
        $status = (string) $row['status'];
        $colorClass = match ($status) {
            'onaylandÄ±', 'onaylandı' => 'success',
            'reddedildi' => 'danger',
            default => 'warning',
        };

        upsert_notification([
            'user_id' => $userId,
            'type' => 'talep_durumu',
            'title' => 'Araç talep durumu',
            'message' => $status . ' • ' . (string) $row['usage_purpose'],
            'related_module' => 'requests',
            'related_record_id' => (int) $row['id'],
            'url' => app_url('personnel/requests.php'),
            'icon' => '📌',
            'color_class' => $colorClass,
            'unique_key' => 'personnel_request_' . (int) $row['id'] . '_' . md5($status),
        ]);
    }
}

function notification_unread_count(int $userId): int
{
    if (!notifications_schema_ready()) {
        return 0;
    }

    return count_value(
        'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0',
        ['user_id' => $userId]
    );
}

function notification_recent_list(int $userId, int $limit = 8): array
{
    if (!notifications_schema_ready()) {
        return [];
    }

    $rows = fetch_all(
        'SELECT * FROM notifications
         WHERE user_id = :user_id
         ORDER BY created_at DESC
         LIMIT ' . (int) $limit,
        ['user_id' => $userId]
    );

    foreach ($rows as &$row) {
        $row['time_ago'] = relative_time_tr((string) $row['created_at']);
    }
    unset($row);

    return $rows;
}

function notification_mark_read(int $userId, int $notificationId): void
{
    if (!notifications_schema_ready()) {
        return;
    }
    execute_query(
        'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = :id AND user_id = :user_id',
        ['id' => $notificationId, 'user_id' => $userId]
    );
}

function notification_mark_all_read(int $userId): void
{
    if (!notifications_schema_ready()) {
        return;
    }
    execute_query(
        'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = :user_id AND is_read = 0',
        ['user_id' => $userId]
    );
}

function create_password_reset_token(array $user): string
{
    execute_query('UPDATE password_resets SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL', [
        'user_id' => (int) $user['id'],
    ]);

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    execute_query(
        'INSERT INTO password_resets (user_id, email, token_hash, expires_at, created_at)
         VALUES (:user_id, :email, :token_hash, DATE_ADD(NOW(), INTERVAL 30 MINUTE), NOW())',
        [
            'user_id' => (int) $user['id'],
            'email' => (string) $user['email'],
            'token_hash' => $tokenHash,
        ]
    );

    return $token;
}

function find_valid_reset_record(string $token): ?array
{
    if ($token === '') {
        return null;
    }

    return fetch_one(
        'SELECT pr.*, u.id AS account_id, u.username, u.is_active
         FROM password_resets pr
         INNER JOIN users u ON u.id = pr.user_id
         WHERE pr.token_hash = :token_hash
           AND pr.used_at IS NULL
           AND pr.expires_at >= NOW()
           AND u.is_active = 1
         ORDER BY pr.id DESC
         LIMIT 1',
        ['token_hash' => hash('sha256', $token)]
    );
}

function mark_reset_token_used(int $resetId): void
{
    execute_query('UPDATE password_resets SET used_at = NOW() WHERE id = :id', ['id' => $resetId]);
}

function send_password_reset_email(string $toEmail, string $resetUrl): bool
{
    $subject = APP_NAME . ' - Şifre Yenileme';
    $html = '<p>Merhaba,</p>'
        . '<p>Şifrenizi yenilemek için aşağıdaki bağlantıyı kullanın:</p>'
        . '<p><a href="' . e($resetUrl) . '">' . e($resetUrl) . '</a></p>'
        . '<p>Bağlantı 30 dakika boyunca geçerlidir.</p>';
    $text = "Merhaba,\n\nŞifrenizi yenilemek için bağlantıyı kullanın:\n"
        . $resetUrl
        . "\n\nBağlantı 30 dakika boyunca geçerlidir.";

    return send_email_via_gmail($toEmail, $subject, $html, $text);
}

function send_email_via_gmail(string $toEmail, string $subject, string $htmlBody, string $textBody = ''): bool
{
    if (trim(GMAIL_SMTP_USER) === '' || trim(GMAIL_SMTP_PASS) === '') {
        return false;
    }

    $socket = @stream_socket_client(
        'ssl://' . GMAIL_SMTP_HOST . ':' . GMAIL_SMTP_PORT,
        $errno,
        $errstr,
        20
    );

    if (!$socket) {
        return false;
    }

    stream_set_timeout($socket, 20);
    $ok = legacy_smtp_expect($socket, [220])
        && legacy_smtp_command($socket, 'EHLO localhost', [250])
        && legacy_smtp_command($socket, 'AUTH LOGIN', [334])
        && legacy_smtp_command($socket, base64_encode(GMAIL_SMTP_USER), [334])
        && legacy_smtp_command($socket, base64_encode(GMAIL_SMTP_PASS), [235])
        && legacy_smtp_command($socket, 'MAIL FROM:<' . MAIL_FROM_ADDRESS . '>', [250])
        && legacy_smtp_command($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251])
        && legacy_smtp_command($socket, 'DATA', [354]);

    if (!$ok) {
        fclose($socket);
        return false;
    }

    $boundary = 'b' . bin2hex(random_bytes(12));
    $headers = [
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>',
        'To: <' . $toEmail . '>',
        'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];

    $plain = $textBody !== '' ? $textBody : strip_tags($htmlBody);
    $message = implode("\r\n", $headers)
        . "\r\n\r\n--" . $boundary . "\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($plain))
        . "--" . $boundary . "\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($htmlBody))
        . "--" . $boundary . "--\r\n.";

    fwrite($socket, $message . "\r\n");
    $sent = legacy_smtp_expect($socket, [250]);
    legacy_smtp_command($socket, 'QUIT', [221]);
    fclose($socket);

    return $sent;
}

function legacy_smtp_command($socket, string $command, array $expectedCodes): bool
{
    fwrite($socket, $command . "\r\n");
    return legacy_smtp_expect($socket, $expectedCodes);
}

function legacy_smtp_expect($socket, array $expectedCodes): bool
{
    $response = '';
    while (($line = fgets($socket, 512)) !== false) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    if (strlen($response) < 3) {
        return false;
    }

    $code = (int) substr($response, 0, 3);
    return in_array($code, $expectedCodes, true);
}

function secure_password_reset_request_is_allowed(?array $user): array
{
    $retryAfter = 0;
    $cooldown = (int) PASSWORD_RESET_RESEND_COOLDOWN_SECONDS;

    if ($user) {
        $last = fetch_one(
            'SELECT created_at FROM password_resets WHERE user_id = :user_id ORDER BY id DESC LIMIT 1',
            ['user_id' => (int) $user['id']]
        );
        if (!empty($last['created_at'])) {
            $elapsed = time() - strtotime((string) $last['created_at']);
            $retryAfter = max(0, $cooldown - $elapsed);
        }

        $windowStart = date('Y-m-d H:i:s', time() - 3600);
        $hourlyCount = count_value(
            'SELECT COUNT(*) FROM password_resets WHERE user_id = :user_id AND created_at >= :window_start',
            [
                'user_id' => (int) $user['id'],
                'window_start' => $windowStart,
            ]
        );
        if ($hourlyCount >= (int) PASSWORD_RESET_MAX_ATTEMPTS_PER_HOUR) {
            return ['allowed' => false, 'retry_after' => 3600];
        }
    } else {
        $sessionLast = (int) ($_SESSION['reset_last_request_at'] ?? 0);
        if ($sessionLast > 0) {
            $elapsed = time() - $sessionLast;
            $retryAfter = max(0, $cooldown - $elapsed);
        }
    }

    if ($retryAfter > 0) {
        return ['allowed' => false, 'retry_after' => $retryAfter];
    }

    return ['allowed' => true, 'retry_after' => 0];
}

function secure_register_password_reset_attempt(): void
{
    $_SESSION['reset_last_request_at'] = time();
}

function secure_invalidate_password_reset_tokens(int $userId): void
{
    execute_query(
        'UPDATE password_resets SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL',
        ['user_id' => $userId]
    );
}

function secure_create_password_reset_token(array $user): string
{
    secure_invalidate_password_reset_tokens((int) $user['id']);

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + ((int) PASSWORD_RESET_TOKEN_TTL_MINUTES * 60));

    $columns = ['user_id', 'email', 'token_hash', 'expires_at', 'created_at'];
    $placeholders = [':user_id', ':email', ':token_hash', ':expires_at', 'NOW()'];
    $params = [
        'user_id' => (int) $user['id'],
        'email' => (string) $user['email'],
        'token_hash' => $tokenHash,
        'expires_at' => $expiresAt,
    ];

    if (column_exists('password_resets', 'ip_address')) {
        $columns[] = 'ip_address';
        $placeholders[] = ':ip_address';
        $params['ip_address'] = mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45, 'UTF-8');
    }

    if (column_exists('password_resets', 'user_agent')) {
        $columns[] = 'user_agent';
        $placeholders[] = ':user_agent';
        $params['user_agent'] = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255, 'UTF-8');
    }

    execute_query(
        'INSERT INTO password_resets (' . implode(', ', $columns) . ')
         VALUES (' . implode(', ', $placeholders) . ')',
        $params
    );

    return $token;
}

function secure_password_strength_messages(string $password): array
{
    $errors = [];
    if (mb_strlen($password, 'UTF-8') < 10) {
        $errors[] = 'Şifre en az 10 karakter olmalıdır.';
    }
    if (!preg_match('/[A-ZÇĞİÖŞÜ]/u', $password)) {
        $errors[] = 'Şifre en az bir büyük harf içermelidir.';
    }
    if (!preg_match('/[a-zçğıöşü]/u', $password)) {
        $errors[] = 'Şifre en az bir küçük harf içermelidir.';
    }
    if (!preg_match('/\d/u', $password)) {
        $errors[] = 'Şifre en az bir rakam içermelidir.';
    }
    if (!preg_match('/[^\p{L}\p{N}\s]/u', $password)) {
        $errors[] = 'Şifre en az bir özel karakter içermelidir.';
    }
    return $errors;
}

function secure_send_password_reset_email(string $toEmail, string $fullName, string $resetUrl): bool
{
    $expireMinutes = (int) PASSWORD_RESET_TOKEN_TTL_MINUTES;
    $name = trim($fullName) !== '' ? $fullName : 'Kullanıcı';
    $subject = APP_NAME . ' - Şifre Sıfırlama';

    $html = '
    <div style="margin:0;padding:24px;background:#f1f5fb;font-family:Segoe UI,Tahoma,Arial,sans-serif;color:#1e2f49;">
        <div style="max-width:620px;margin:0 auto;background:#ffffff;border:1px solid #d8e2f0;border-radius:14px;overflow:hidden;">
            <div style="padding:20px 24px;background:linear-gradient(135deg,#1d4576 0%,#275f9f 100%);color:#fff;">
                <h1 style="margin:0;font-size:22px;line-height:1.3;">' . e(APP_NAME) . '</h1>
                <p style="margin:8px 0 0;font-size:14px;opacity:.92;">Şifre sıfırlama talebiniz alındı.</p>
            </div>
            <div style="padding:22px 24px;">
                <p style="margin:0 0 12px;">Merhaba ' . e($name) . ',</p>
                <p style="margin:0 0 14px;line-height:1.55;">Bu işlemi siz yaptıysanız aşağıdaki buton ile şifrenizi yenileyin.</p>
                <p style="margin:20px 0;">
                    <a href="' . e($resetUrl) . '" style="display:inline-block;padding:12px 18px;border-radius:10px;background:#2d74af;color:#fff;text-decoration:none;font-weight:700;">Şifremi Sıfırla</a>
                </p>
                <p style="margin:0 0 8px;font-size:14px;line-height:1.5;">Bağlantı <strong>' . $expireMinutes . ' dakika</strong> geçerlidir ve tek kullanımlıktır.</p>
                <p style="margin:0 0 8px;font-size:14px;line-height:1.5;">Buton çalışmazsa şu bağlantıyı kullanın:</p>
                <p style="margin:0 0 14px;word-break:break-all;font-size:13px;color:#2b4d76;">' . e($resetUrl) . '</p>
                <div style="margin-top:14px;padding:12px;border-radius:10px;background:#fff8e8;border:1px solid #f0d9a2;color:#7d5615;font-size:13px;">
                    Bu işlemi siz yapmadıysanız e-postayı dikkate almayın.
                </div>
            </div>
        </div>
    </div>';

    $text = "Merhaba {$name},\n\n"
        . "Şifre sıfırlama talebiniz alındı.\n"
        . "Bağlantı: {$resetUrl}\n\n"
        . "Bağlantı {$expireMinutes} dakika geçerlidir ve tek kullanımlıktır.\n"
        . "Bu işlemi siz yapmadıysanız e-postayı dikkate almayın.";

    return smtp_send_mail($toEmail, $subject, $html, $text);
}

function security_events_schema_ready(): bool
{
    return table_exists('security_events');
}

function log_security_event(
    string $eventType,
    string $status,
    ?int $userId = null,
    ?string $email = null,
    ?string $details = null
): void {
    if (!security_events_schema_ready()) {
        return;
    }

    execute_query(
        'INSERT INTO security_events (user_id, email, event_type, status, details, ip_address, user_agent, created_at)
         VALUES (:user_id, :email, :event_type, :status, :details, :ip_address, :user_agent, NOW())',
        [
            'user_id' => $userId,
            'email' => $email,
            'event_type' => $eventType,
            'status' => $status,
            'details' => $details,
            'ip_address' => mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45, 'UTF-8'),
            'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255, 'UTF-8'),
        ]
    );
}

function backup_tables_ready(): bool
{
    return table_exists('backup_schedules') && table_exists('backup_logs');
}

function backup_next_run_time(string $frequency, ?int $fromTimestamp = null): string
{
    $base = $fromTimestamp ?? time();
    return match ($frequency) {
        'weekly' => date('Y-m-d H:i:s', strtotime('+7 days', $base)),
        'monthly' => date('Y-m-d H:i:s', strtotime('+1 month', $base)),
        default => date('Y-m-d H:i:s', strtotime('+1 day', $base)),
    };
}

function backup_schedule(): ?array
{
    if (!backup_tables_ready()) {
        return null;
    }

    $row = fetch_one('SELECT * FROM backup_schedules WHERE id = 1 LIMIT 1');
    if ($row) {
        return $row;
    }

    execute_query(
        'INSERT INTO backup_schedules (id, frequency, is_active, next_run_at, last_status)
         VALUES (1, :frequency, 1, :next_run_at, :last_status)',
        [
            'frequency' => 'daily',
            'next_run_at' => backup_next_run_time('daily'),
            'last_status' => 'pending',
        ]
    );

    return fetch_one('SELECT * FROM backup_schedules WHERE id = 1 LIMIT 1');
}

function update_backup_schedule(string $frequency, bool $isActive): void
{
    if (!backup_tables_ready()) {
        return;
    }

    $frequency = in_array($frequency, ['daily', 'weekly', 'monthly'], true) ? $frequency : 'daily';

    execute_query(
        'UPDATE backup_schedules
         SET frequency = :frequency,
             is_active = :is_active,
             next_run_at = :next_run_at
         WHERE id = 1',
        [
            'frequency' => $frequency,
            'is_active' => $isActive ? 1 : 0,
            'next_run_at' => $isActive ? backup_next_run_time($frequency) : null,
        ]
    );
}

function resolve_mysqldump_binary(): string
{
    if (trim((string) MYSQLDUMP_PATH) !== '') {
        return (string) MYSQLDUMP_PATH;
    }

    $candidates = [
        'C:\\xampp\\mysql\\bin\\mysqldump.exe',
        'C:\\xampp\\mysql\\bin\\mysqldump',
        'mysqldump',
    ];

    foreach ($candidates as $candidate) {
        if ($candidate === 'mysqldump' || is_file($candidate)) {
            return $candidate;
        }
    }

    return 'mysqldump';
}

function shell_quote(string $value): string
{
    return '"' . str_replace('"', '\"', $value) . '"';
}

function run_database_backup(?int $triggeredByUserId = null, string $runType = 'manual'): array
{
    if (!backup_tables_ready()) {
        return ['ok' => false, 'message' => 'Yedekleme tabloları bulunamadı.'];
    }

    $backupDir = __DIR__ . '/../storage/backups';
    if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
        return ['ok' => false, 'message' => 'Yedek klasörü oluşturulamadı.'];
    }

    $fileName = 'backup_' . date('Ymd_His') . '.sql';
    $fullPath = realpath($backupDir) . DIRECTORY_SEPARATOR . $fileName;
    $binary = resolve_mysqldump_binary();

    $parts = [
        shell_quote($binary),
        '--single-transaction',
        '--default-character-set=utf8mb4',
        '--skip-lock-tables',
        '--host=' . shell_quote(DB_HOST),
        '--port=' . (int) DB_PORT,
        '--user=' . shell_quote(DB_USER),
    ];

    if (DB_PASS !== '') {
        $parts[] = '--password=' . shell_quote(DB_PASS);
    }

    $parts[] = shell_quote(DB_NAME);
    $command = implode(' ', $parts) . ' > ' . shell_quote($fullPath) . ' 2>&1';

    execute_query(
        'INSERT INTO backup_logs (run_type, status, backup_file, triggered_by, started_at)
         VALUES (:run_type, :status, :backup_file, :triggered_by, NOW())',
        [
            'run_type' => $runType,
            'status' => 'failed',
            'backup_file' => $fileName,
            'triggered_by' => $triggeredByUserId,
        ]
    );
    $logId = (int) db()->lastInsertId();

    $output = [];
    $exitCode = 1;
    @exec($command, $output, $exitCode);
    $errorMessage = trim(implode("\n", $output));
    $ok = ($exitCode === 0) && is_file($fullPath) && filesize($fullPath) > 0;

    execute_query(
        'UPDATE backup_logs
         SET status = :status, error_message = :error_message, finished_at = NOW()
         WHERE id = :id',
        [
            'status' => $ok ? 'success' : 'failed',
            'error_message' => $ok ? null : mb_substr($errorMessage ?: 'Yedekleme komutu başarısız oldu.', 0, 255, 'UTF-8'),
            'id' => $logId,
        ]
    );

    $schedule = backup_schedule();
    if ($schedule) {
        $frequency = (string) $schedule['frequency'];
        execute_query(
            'UPDATE backup_schedules
             SET last_run_at = NOW(),
                 last_status = :last_status,
                 last_error = :last_error,
                 next_run_at = :next_run_at
             WHERE id = 1',
            [
                'last_status' => $ok ? 'success' : 'failed',
                'last_error' => $ok ? null : mb_substr($errorMessage ?: 'Bilinmeyen hata', 0, 255, 'UTF-8'),
                'next_run_at' => backup_next_run_time($frequency),
            ]
        );
    }

    return [
        'ok' => $ok,
        'message' => $ok ? 'Veritabanı yedeği başarıyla oluşturuldu.' : 'Yedekleme işlemi başarısız oldu: ' . ($errorMessage ?: 'Bilinmeyen hata'),
        'file' => $ok ? $fileName : null,
    ];
}
