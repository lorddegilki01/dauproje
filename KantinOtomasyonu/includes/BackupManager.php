<?php
declare(strict_types=1);

final class BackupManager
{
    private PDO $pdo;
    private string $projectRoot;

    public function __construct(PDO $pdo, ?string $projectRoot = null)
    {
        $this->pdo = $pdo;
        $this->projectRoot = $projectRoot ?? dirname(__DIR__);
    }

    public function ensureSchema(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS backup_settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                automatic_enabled TINYINT(1) NOT NULL DEFAULT 0,
                frequency ENUM("daily","weekly","monthly") NOT NULL DEFAULT "daily",
                backup_time CHAR(5) NOT NULL DEFAULT "03:00",
                max_backup_count INT UNSIGNED NOT NULL DEFAULT 30,
                backup_path VARCHAR(255) NOT NULL,
                auto_cleanup_enabled TINYINT(1) NOT NULL DEFAULT 1,
                last_success_at DATETIME NULL,
                last_failed_at DATETIME NULL,
                next_run_at DATETIME NULL,
                last_error TEXT NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS backups (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                file_name VARCHAR(255) NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                backup_type ENUM("manuel","otomatik") NOT NULL DEFAULT "manuel",
                status ENUM("başarılı","başarısız","işleniyor") NOT NULL DEFAULT "işleniyor",
                file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
                checksum VARCHAR(128) NULL,
                error_message TEXT NULL,
                started_by INT UNSIGNED NULL,
                started_at DATETIME NOT NULL,
                finished_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_backups_user FOREIGN KEY (started_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_backups_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci'
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS backup_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                backup_id INT UNSIGNED NULL,
                action VARCHAR(100) NOT NULL,
                status ENUM("başarılı","başarısız","bilgi") NOT NULL DEFAULT "bilgi",
                message TEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_backup_logs_backup FOREIGN KEY (backup_id) REFERENCES backups(id) ON DELETE CASCADE,
                INDEX idx_backup_logs_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci'
        );

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM backup_settings')->fetchColumn();
        if ($count === 0) {
            $defaultPath = $this->normalizeBackupPath('storage/backups');
            $this->ensureBackupDirectory($defaultPath);
            $stmt = $this->pdo->prepare(
                'INSERT INTO backup_settings
                (automatic_enabled, frequency, backup_time, max_backup_count, backup_path, auto_cleanup_enabled, next_run_at)
                VALUES (0, "daily", "03:00", 30, :backup_path, 1, :next_run_at)'
            );
            $stmt->execute([
                'backup_path' => $defaultPath,
                'next_run_at' => $this->calculateNextRunAt(['frequency' => 'daily', 'backup_time' => '03:00'], new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
        }
    }

    public function getSettings(): array
    {
        $this->ensureSchema();
        $row = $this->pdo->query('SELECT * FROM backup_settings ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new RuntimeException('Yedekleme ayarları bulunamadı.');
        }
        return $row;
    }

    public function updateSettings(array $input, int $userId): void
    {
        $settings = $this->getSettings();
        $automatic = isset($input['automatic_enabled']) ? 1 : 0;
        $cleanup = isset($input['auto_cleanup_enabled']) ? 1 : 0;
        $frequency = (string) ($input['frequency'] ?? 'daily');
        $backupTime = (string) ($input['backup_time'] ?? '03:00');
        $maxCount = max(1, min(500, (int) ($input['max_backup_count'] ?? 30)));
        $backupPath = (string) ($input['backup_path'] ?? 'storage/backups');

        if (!in_array($frequency, ['daily', 'weekly', 'monthly'], true)) {
            throw new RuntimeException('Geçersiz yedekleme sıklığı.');
        }
        if (!preg_match('/^\d{2}:\d{2}$/', $backupTime)) {
            throw new RuntimeException('Yedekleme saati HH:MM formatında olmalıdır.');
        }

        $path = $this->normalizeBackupPath($backupPath);
        $this->ensureBackupDirectory($path);

        $nextRun = null;
        if ($automatic === 1) {
            $nextRun = $this->calculateNextRunAt(['frequency' => $frequency, 'backup_time' => $backupTime], new DateTimeImmutable())->format('Y-m-d H:i:s');
        }

        $stmt = $this->pdo->prepare(
            'UPDATE backup_settings
             SET automatic_enabled = :automatic_enabled,
                 frequency = :frequency,
                 backup_time = :backup_time,
                 max_backup_count = :max_backup_count,
                 backup_path = :backup_path,
                 auto_cleanup_enabled = :auto_cleanup_enabled,
                 next_run_at = :next_run_at
             WHERE id = :id'
        );
        $stmt->execute([
            'automatic_enabled' => $automatic,
            'frequency' => $frequency,
            'backup_time' => $backupTime,
            'max_backup_count' => $maxCount,
            'backup_path' => $path,
            'auto_cleanup_enabled' => $cleanup,
            'next_run_at' => $nextRun,
            'id' => $settings['id'],
        ]);

        $this->addLog(null, 'ayar_güncellendi', 'bilgi', 'Yedekleme ayarları güncellendi. Kullanıcı ID: ' . $userId);
    }

    public function createBackup(string $type = 'manuel', ?int $startedBy = null, bool $scheduled = false): array
    {
        $this->ensureSchema();
        $settings = $this->getSettings();
        $backupPath = $this->normalizeBackupPath((string) $settings['backup_path']);
        $this->ensureBackupDirectory($backupPath);

        $startedAt = new DateTimeImmutable();
        $fileName = sprintf('kantin_backup_%s.sql', $startedAt->format('Y_m_d_H_i_s'));
        $filePath = $backupPath . DIRECTORY_SEPARATOR . $fileName;

        $insert = $this->pdo->prepare(
            'INSERT INTO backups (file_name, file_path, backup_type, status, started_by, started_at)
             VALUES (:file_name, :file_path, :backup_type, "işleniyor", :started_by, :started_at)'
        );
        $insert->execute([
            'file_name' => $fileName,
            'file_path' => $filePath,
            'backup_type' => $type,
            'started_by' => $startedBy,
            'started_at' => $startedAt->format('Y-m-d H:i:s'),
        ]);
        $backupId = (int) $this->pdo->lastInsertId();

        try {
            $sqlDump = $this->dumpWithMysqldump();
            if ($sqlDump === null || trim($sqlDump) === '') {
                $sqlDump = $this->dumpWithPhpExporter();
            }

            if ($sqlDump === '' || trim($sqlDump) === '') {
                throw new RuntimeException('Yedek içeriği üretilemedi.');
            }

            file_put_contents($filePath, $sqlDump, LOCK_EX);
            clearstatcache(true, $filePath);

            $fileSize = file_exists($filePath) ? (int) filesize($filePath) : 0;
            if ($fileSize <= 0) {
                throw new RuntimeException('Yedek dosyası oluşturuldu ancak boş görünüyor.');
            }
            $checksum = hash_file('sha256', $filePath);
            $finishedAt = new DateTimeImmutable();

            $update = $this->pdo->prepare(
                'UPDATE backups
                 SET status = "başarılı",
                     file_size = :file_size,
                     checksum = :checksum,
                     finished_at = :finished_at
                 WHERE id = :id'
            );
            $update->execute([
                'file_size' => $fileSize,
                'checksum' => $checksum,
                'finished_at' => $finishedAt->format('Y-m-d H:i:s'),
                'id' => $backupId,
            ]);

            $this->addLog($backupId, 'yedekleme_başarılı', 'başarılı', strtoupper($type) . ' yedekleme başarıyla tamamlandı: ' . $fileName);
            $this->updateSettingsAfterRun(true, null, $settings, $scheduled);

            $deletedCount = 0;
            if ((int) $settings['auto_cleanup_enabled'] === 1) {
                $deletedCount = $this->cleanupOldBackups((int) $settings['max_backup_count']);
                if ($deletedCount > 0) {
                    $this->addLog($backupId, 'otomatik_temizlik', 'bilgi', $deletedCount . ' eski yedek temizlendi.');
                }
            }

            return [
                'success' => true,
                'backup_id' => $backupId,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'checksum' => $checksum,
                'deleted_count' => $deletedCount,
            ];
        } catch (Throwable $e) {
            $finishedAt = new DateTimeImmutable();
            $message = mb_substr($e->getMessage(), 0, 2000, 'UTF-8');

            $update = $this->pdo->prepare(
                'UPDATE backups
                 SET status = "başarısız",
                     error_message = :error_message,
                     finished_at = :finished_at
                 WHERE id = :id'
            );
            $update->execute([
                'error_message' => $message,
                'finished_at' => $finishedAt->format('Y-m-d H:i:s'),
                'id' => $backupId,
            ]);

            $this->addLog($backupId, 'yedekleme_hatası', 'başarısız', 'Yedekleme başarısız: ' . $message);
            $this->updateSettingsAfterRun(false, $message, $settings, $scheduled);

            return [
                'success' => false,
                'backup_id' => $backupId,
                'error' => $message,
            ];
        }
    }

    public function runScheduledBackupIfDue(): bool
    {
        $settings = $this->getSettings();
        if ((int) $settings['automatic_enabled'] !== 1) {
            return false;
        }

        $nextRunRaw = (string) ($settings['next_run_at'] ?? '');
        if ($nextRunRaw === '') {
            $next = $this->calculateNextRunAt($settings, new DateTimeImmutable());
            $this->setNextRun($next);
            return false;
        }

        $nextRun = new DateTimeImmutable($nextRunRaw);
        if ($nextRun > new DateTimeImmutable()) {
            return false;
        }

        $result = $this->createBackup('otomatik', null, true);
        return (bool) ($result['success'] ?? false);
    }

    public function listBackups(int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $sql = 'SELECT b.*, u.full_name
                FROM backups b
                LEFT JOIN users u ON u.id = b.started_by
                ORDER BY b.created_at DESC
                LIMIT ' . $limit;
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getBackupById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.*, u.full_name
             FROM backups b
             LEFT JOIN users u ON u.id = b.started_by
             WHERE b.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getStats(): array
    {
        $settings = $this->getSettings();
        $row = $this->pdo->query(
            'SELECT
                COUNT(*) AS total_count,
                COALESCE(SUM(file_size), 0) AS total_size,
                MAX(CASE WHEN status = "başarılı" THEN created_at END) AS last_success,
                MAX(CASE WHEN status = "başarısız" THEN created_at END) AS last_failed
             FROM backups'
        )->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'automatic_enabled' => (int) $settings['automatic_enabled'],
            'frequency' => (string) $settings['frequency'],
            'backup_time' => (string) $settings['backup_time'],
            'max_backup_count' => (int) $settings['max_backup_count'],
            'backup_path' => (string) $settings['backup_path'],
            'auto_cleanup_enabled' => (int) $settings['auto_cleanup_enabled'],
            'last_success_at' => $settings['last_success_at'] ?? ($row['last_success'] ?? null),
            'last_failed_at' => $settings['last_failed_at'] ?? ($row['last_failed'] ?? null),
            'next_run_at' => $settings['next_run_at'] ?? null,
            'last_error' => $settings['last_error'] ?? null,
            'total_count' => (int) ($row['total_count'] ?? 0),
            'total_size' => (int) ($row['total_size'] ?? 0),
        ];
    }

    public function getRecentLogs(int $limit = 100): array
    {
        $limit = max(1, min(300, $limit));
        $sql = 'SELECT bl.*, b.file_name
                FROM backup_logs bl
                LEFT JOIN backups b ON b.id = bl.backup_id
                ORDER BY bl.created_at DESC
                LIMIT ' . $limit;
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function deleteBackup(int $id, int $userId): array
    {
        $backup = $this->getBackupById($id);
        if (!$backup) {
            return ['success' => false, 'error' => 'Yedek kaydı bulunamadı.'];
        }

        $filePath = (string) $backup['file_path'];
        if (!$this->isPathInsideProject($filePath)) {
            return ['success' => false, 'error' => 'Dosya yolu güvenlik doğrulamasını geçemedi.'];
        }

        $this->pdo->beginTransaction();
        try {
            $fileDeleted = true;
            if (is_file($filePath)) {
                $fileDeleted = @unlink($filePath);
            }
            if (!$fileDeleted) {
                throw new RuntimeException('Yedek dosyası silinemedi.');
            }

            $stmt = $this->pdo->prepare('DELETE FROM backups WHERE id = :id');
            $stmt->execute(['id' => $id]);

            $this->addLog(null, 'yedek_silindi', 'bilgi', 'Yedek silindi: ' . $backup['file_name'] . ' (Kullanıcı ID: ' . $userId . ')');
            $this->pdo->commit();

            return ['success' => true];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function humanFileSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        return number_format($bytes / (1024 ** $power), 2, ',', '.') . ' ' . $units[$power];
    }

    private function updateSettingsAfterRun(bool $success, ?string $error, array $settings, bool $scheduled): void
    {
        $now = new DateTimeImmutable();
        $nextRun = null;
        if ((int) $settings['automatic_enabled'] === 1) {
            $nextRun = $this->calculateNextRunAt($settings, $now)->format('Y-m-d H:i:s');
        }

        if ($success) {
            $stmt = $this->pdo->prepare(
                'UPDATE backup_settings
                 SET last_success_at = :now,
                     next_run_at = :next_run_at,
                     last_error = NULL
                 WHERE id = :id'
            );
            $stmt->execute([
                'now' => $now->format('Y-m-d H:i:s'),
                'next_run_at' => $nextRun,
                'id' => $settings['id'],
            ]);
            if ($scheduled) {
                $this->addLog(null, 'otomatik_yedekleme_başarılı', 'başarılı', 'Otomatik yedekleme başarıyla tamamlandı.');
            } else {
                $this->addLog(null, 'manuel_yedekleme_başarılı', 'başarılı', 'Manuel yedekleme başarıyla tamamlandı.');
            }
            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE backup_settings
             SET last_failed_at = :now,
                 next_run_at = :next_run_at,
                 last_error = :last_error
             WHERE id = :id'
        );
        $stmt->execute([
            'now' => $now->format('Y-m-d H:i:s'),
            'next_run_at' => $nextRun,
            'last_error' => $error,
            'id' => $settings['id'],
        ]);
    }

    private function setNextRun(DateTimeImmutable $next): void
    {
        $settings = $this->getSettings();
        $stmt = $this->pdo->prepare('UPDATE backup_settings SET next_run_at = :next_run_at WHERE id = :id');
        $stmt->execute([
            'next_run_at' => $next->format('Y-m-d H:i:s'),
            'id' => $settings['id'],
        ]);
    }

    private function calculateNextRunAt(array $settings, DateTimeImmutable $from): DateTimeImmutable
    {
        $time = (string) ($settings['backup_time'] ?? '03:00');
        [$hour, $minute] = array_map('intval', explode(':', $time));
        $base = $from->setTime($hour, $minute, 0);
        $frequency = (string) ($settings['frequency'] ?? 'daily');

        if ($frequency === 'weekly') {
            if ($base <= $from) {
                $base = $base->modify('+7 day');
            }
            return $base;
        }

        if ($frequency === 'monthly') {
            $day = (int) $from->format('d');
            $candidate = $from->setDate((int) $from->format('Y'), (int) $from->format('m'), $day)->setTime($hour, $minute, 0);
            if ($candidate <= $from) {
                $candidate = $candidate->modify('first day of next month')->setDate(
                    (int) $candidate->format('Y'),
                    (int) $candidate->format('m'),
                    min($day, (int) $candidate->format('t'))
                )->setTime($hour, $minute, 0);
            }
            return $candidate;
        }

        if ($base <= $from) {
            $base = $base->modify('+1 day');
        }
        return $base;
    }

    private function cleanupOldBackups(int $maxKeep): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, file_path
             FROM backups
             WHERE status = "başarılı"
             ORDER BY created_at DESC
             LIMIT 10000'
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (count($rows) <= $maxKeep) {
            return 0;
        }

        $toDelete = array_slice($rows, $maxKeep);
        $deleted = 0;

        foreach ($toDelete as $row) {
            $id = (int) $row['id'];
            $path = (string) $row['file_path'];
            if ($this->isPathInsideProject($path) && is_file($path)) {
                @unlink($path);
            }
            $del = $this->pdo->prepare('DELETE FROM backups WHERE id = :id');
            $del->execute(['id' => $id]);
            $deleted++;
        }

        return $deleted;
    }

    private function addLog(?int $backupId, string $action, string $status, string $message): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO backup_logs (backup_id, action, status, message, created_at)
             VALUES (:backup_id, :action, :status, :message, NOW())'
        );
        $stmt->bindValue('backup_id', $backupId, $backupId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('action', $action, PDO::PARAM_STR);
        $stmt->bindValue('status', $status, PDO::PARAM_STR);
        $stmt->bindValue('message', $message, PDO::PARAM_STR);
        $stmt->execute();
    }

    private function dumpWithMysqldump(): ?string
    {
        $binCandidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump',
            'mysqldump',
        ];

        $binary = null;
        foreach ($binCandidates as $candidate) {
            if ($candidate === 'mysqldump' || file_exists($candidate)) {
                $binary = $candidate;
                break;
            }
        }
        if ($binary === null) {
            return null;
        }

        $parts = [
            escapeshellarg($binary),
            '--skip-comments',
            '--single-transaction',
            '--default-character-set=utf8mb4',
            '--host=' . escapeshellarg(DB_HOST),
            '--port=' . escapeshellarg(DB_PORT),
            '--user=' . escapeshellarg(DB_USER),
        ];

        if (DB_PASS !== '') {
            $parts[] = '--password=' . escapeshellarg(DB_PASS);
        }

        $parts[] = escapeshellarg(DB_NAME);
        $cmd = implode(' ', $parts) . ' 2>&1';

        $output = [];
        $exitCode = 0;
        @exec($cmd, $output, $exitCode);
        if ($exitCode !== 0 || empty($output)) {
            return null;
        }

        $dump = implode(PHP_EOL, $output);
        if (stripos($dump, 'Access denied') !== false || stripos($dump, 'not recognized') !== false) {
            return null;
        }
        return $dump . PHP_EOL;
    }

    private function dumpWithPhpExporter(): string
    {
        $sql = [];
        $sql[] = 'SET NAMES utf8mb4;';
        $sql[] = 'SET CHARACTER SET utf8mb4;';
        $sql[] = "SET collation_connection = 'utf8mb4_turkish_ci';";
        $sql[] = '';

        $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach ($tables as $table) {
            $tableName = (string) $table;
            $createRow = $this->pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $tableName) . '`')->fetch(PDO::FETCH_ASSOC);
            if (!$createRow || !isset($createRow['Create Table'])) {
                continue;
            }

            $sql[] = 'DROP TABLE IF EXISTS `' . $tableName . '`;';
            $sql[] = $createRow['Create Table'] . ';';
            $sql[] = '';

            $rows = $this->pdo->query('SELECT * FROM `' . str_replace('`', '``', $tableName) . '`')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if (!$rows) {
                continue;
            }

            $columns = array_keys($rows[0]);
            $columnSql = '`' . implode('`,`', array_map(static fn($c) => str_replace('`', '``', (string) $c), $columns)) . '`';

            $valueRows = [];
            foreach ($rows as $row) {
                $values = [];
                foreach ($columns as $column) {
                    $value = $row[$column];
                    if ($value === null) {
                        $values[] = 'NULL';
                    } elseif (is_bool($value)) {
                        $values[] = $value ? '1' : '0';
                    } elseif (is_numeric($value) && !preg_match('/^0\d+$/', (string) $value)) {
                        $values[] = (string) $value;
                    } else {
                        $values[] = $this->pdo->quote((string) $value);
                    }
                }
                $valueRows[] = '(' . implode(',', $values) . ')';
            }

            $sql[] = 'INSERT INTO `' . $tableName . '` (' . $columnSql . ') VALUES';
            $sql[] = implode(',' . PHP_EOL, $valueRows) . ';';
            $sql[] = '';
        }

        return implode(PHP_EOL, $sql) . PHP_EOL;
    }

    private function normalizeBackupPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            $path = 'storage/backups';
        }

        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $isAbsolute = preg_match('/^[A-Za-z]:\\\\/', $path) === 1 || str_starts_with($path, DIRECTORY_SEPARATOR);
        $full = $isAbsolute ? $path : ($this->projectRoot . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR));
        $full = preg_replace('#[\\\\/]+#', DIRECTORY_SEPARATOR, $full) ?: $full;

        $resolvedParent = realpath(dirname($full));
        if ($resolvedParent !== false) {
            $full = $resolvedParent . DIRECTORY_SEPARATOR . basename($full);
        }

        if (!$this->isPathInsideProject($full)) {
            throw new RuntimeException('Yedek klasörü proje dizini içinde olmalıdır.');
        }

        return rtrim($full, DIRECTORY_SEPARATOR);
    }

    private function ensureBackupDirectory(string $path): void
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0775, true) && !is_dir($path)) {
                throw new RuntimeException('Yedek klasörü oluşturulamadı: ' . $path);
            }
        }

        $htaccessPath = $path . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($htaccessPath)) {
            file_put_contents($htaccessPath, "Deny from all\n", LOCK_EX);
        }
        $indexPath = $path . DIRECTORY_SEPARATOR . 'index.html';
        if (!is_file($indexPath)) {
            file_put_contents($indexPath, '', LOCK_EX);
        }
    }

    private function isPathInsideProject(string $path): bool
    {
        $normalizedRoot = strtolower(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, rtrim($this->projectRoot, DIRECTORY_SEPARATOR)));
        $normalizedPath = strtolower(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));
        return str_starts_with($normalizedPath, $normalizedRoot);
    }
}
