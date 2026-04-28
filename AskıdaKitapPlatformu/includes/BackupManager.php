<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

final class BackupManager
{
    public function ensureSettingsRow(): void
    {
        $exists = fetch_one('SELECT id FROM backup_settings WHERE id = 1');
        if ($exists) {
            return;
        }
        execute_query(
            'INSERT INTO backup_settings (id, automatic_enabled, frequency, backup_time, max_backup_count, backup_path, auto_cleanup_enabled, updated_at)
             VALUES (1, 0, "gunluk", "03:00:00", 20, "storage/backups", 1, NOW())'
        );
    }

    public function getSettings(): array
    {
        $this->ensureSettingsRow();
        return fetch_one('SELECT * FROM backup_settings WHERE id = 1') ?? [];
    }

    public function updateSettings(array $data): void
    {
        $current = $this->getSettings();
        $frequency = in_array((string) ($data['frequency'] ?? ''), ['gunluk', 'haftalik', 'aylik'], true) ? (string) $data['frequency'] : 'gunluk';
        $backupTime = preg_match('/^\d{2}:\d{2}(:\d{2})?$/', (string) ($data['backup_time'] ?? '')) ? (string) $data['backup_time'] : '03:00:00';
        if (strlen($backupTime) === 5) {
            $backupTime .= ':00';
        }
        $maxBackupCount = max(3, min(500, (int) ($data['max_backup_count'] ?? 20)));
        $backupPath = trim((string) ($data['backup_path'] ?? 'storage/backups'));
        $backupPath = str_replace(['..', '\\'], ['', '/'], $backupPath);
        if ($backupPath === '') {
            $backupPath = 'storage/backups';
        }

        $automaticEnabled = !empty($data['automatic_enabled']) ? 1 : 0;
        $autoCleanupEnabled = !empty($data['auto_cleanup_enabled']) ? 1 : 0;
        $nextRunAt = $automaticEnabled === 1 ? $this->computeNextRun($frequency, $backupTime, new DateTimeImmutable('now')) : null;

        execute_query(
            'UPDATE backup_settings
             SET automatic_enabled = :automatic_enabled,
                 frequency = :frequency,
                 backup_time = :backup_time,
                 max_backup_count = :max_backup_count,
                 backup_path = :backup_path,
                 auto_cleanup_enabled = :auto_cleanup_enabled,
                 next_run_at = :next_run_at,
                 updated_at = NOW()
             WHERE id = 1',
            [
                'automatic_enabled' => $automaticEnabled,
                'frequency' => $frequency,
                'backup_time' => $backupTime,
                'max_backup_count' => $maxBackupCount,
                'backup_path' => $backupPath,
                'auto_cleanup_enabled' => $autoCleanupEnabled,
                'next_run_at' => $nextRunAt,
            ]
        );

        if ((string) ($current['backup_path'] ?? '') !== $backupPath) {
            $this->ensureStoragePath($backupPath);
        }
    }

    public function createBackup(string $type, ?int $userId): array
    {
        $settings = $this->getSettings();
        $relativePath = (string) ($settings['backup_path'] ?? 'storage/backups');
        $storageDir = $this->ensureStoragePath($relativePath);
        $timestamp = date('Y_m_d_H_i_s');
        $fileName = "askida_kitap_backup_{$timestamp}.sql";
        $fullPath = $storageDir . DIRECTORY_SEPARATOR . $fileName;

        execute_query(
            'INSERT INTO backups (file_name, file_path, backup_type, status, started_by, started_at, created_at)
             VALUES (:file_name, :file_path, :backup_type, "basarisiz", :started_by, NOW(), NOW())',
            [
                'file_name' => $fileName,
                'file_path' => $fullPath,
                'backup_type' => $type,
                'started_by' => $userId,
            ]
        );
        $backupId = (int) db()->lastInsertId();

        $ok = false;
        $errorMessage = null;
        try {
            $ok = $this->tryMysqldump($fullPath);
            if (!$ok) {
                $ok = $this->runPhpExport($fullPath);
            }
        } catch (Throwable $e) {
            $ok = false;
            $errorMessage = $e->getMessage();
        }

        if ($ok) {
            $fileSize = is_file($fullPath) ? (int) filesize($fullPath) : 0;
            $checksum = is_file($fullPath) ? hash_file('sha256', $fullPath) : null;
            execute_query(
                'UPDATE backups
                 SET status = "basarili", file_size = :file_size, checksum = :checksum, error_message = NULL, finished_at = NOW()
                 WHERE id = :id',
                [
                    'id' => $backupId,
                    'file_size' => $fileSize,
                    'checksum' => $checksum,
                ]
            );
            execute_query(
                'UPDATE backup_settings
                 SET last_success_at = NOW(), last_error = NULL, next_run_at = :next_run_at, updated_at = NOW()
                 WHERE id = 1',
                ['next_run_at' => $this->nextFromCurrentSettings()]
            );
            $this->log($backupId, 'backup.create', 'basarili', strtoupper($type) . ' yedekleme başarıyla tamamlandı.');
            create_notification(null, 'Yedekleme Başarılı', 'Veritabanı yedekleme işlemi başarıyla tamamlandı.', 'basari', 'admin/backups.php');
            system_log($userId, 'backup.create', 'basarili', strtoupper($type) . ' yedekleme tamamlandı.');
            $this->cleanupIfNeeded();

            return ['success' => true, 'message' => 'Yedek başarıyla oluşturuldu.'];
        }

        if ($errorMessage === null) {
            $errorMessage = 'Yedek dosyası oluşturulamadı.';
        }
        execute_query(
            'UPDATE backups
             SET status = "basarisiz", file_size = 0, checksum = NULL, error_message = :error_message, finished_at = NOW()
             WHERE id = :id',
            [
                'id' => $backupId,
                'error_message' => mb_substr($errorMessage, 0, 255, 'UTF-8'),
            ]
        );
        execute_query(
            'UPDATE backup_settings
             SET last_failed_at = NOW(), last_error = :last_error, next_run_at = :next_run_at, updated_at = NOW()
             WHERE id = 1',
            [
                'last_error' => mb_substr($errorMessage, 0, 255, 'UTF-8'),
                'next_run_at' => $this->nextFromCurrentSettings(),
            ]
        );
        $this->log($backupId, 'backup.create', 'basarisiz', $errorMessage);
        create_notification(null, 'Yedekleme Hatası', 'Veritabanı yedeklemesi başarısız oldu: ' . $errorMessage, 'hata', 'admin/backups.php');
        system_log($userId, 'backup.create', 'basarisiz', $errorMessage);

        return ['success' => false, 'message' => $errorMessage];
    }

    public function cleanupIfNeeded(): void
    {
        $settings = $this->getSettings();
        if ((int) ($settings['auto_cleanup_enabled'] ?? 0) !== 1) {
            return;
        }
        $maxCount = (int) ($settings['max_backup_count'] ?? 20);
        $backups = fetch_all('SELECT id, file_path FROM backups ORDER BY created_at DESC');
        if (count($backups) <= $maxCount) {
            return;
        }
        $toDelete = array_slice($backups, $maxCount);
        foreach ($toDelete as $row) {
            $this->deleteBackup((int) $row['id'], false);
        }
        create_notification(null, 'Yedek Temizliği', 'Eski yedekler otomatik olarak temizlendi.', 'bilgi', 'admin/backups.php');
    }

    public function deleteBackup(int $backupId, bool $logAction = true): bool
    {
        $backup = fetch_one('SELECT * FROM backups WHERE id = :id', ['id' => $backupId]);
        if (!$backup) {
            return false;
        }
        $path = (string) ($backup['file_path'] ?? '');
        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }
        execute_query('DELETE FROM backup_logs WHERE backup_id = :id', ['id' => $backupId]);
        execute_query('DELETE FROM backups WHERE id = :id', ['id' => $backupId]);
        if ($logAction) {
            $this->log(null, 'backup.delete', 'basarili', 'Yedek silindi: ' . (string) $backup['file_name']);
            create_notification(null, 'Yedek Silindi', (string) $backup['file_name'] . ' yedeği silindi.', 'uyari', 'admin/backups.php');
        }
        return true;
    }

    public function dueForAutomaticRun(): bool
    {
        $settings = $this->getSettings();
        if ((int) ($settings['automatic_enabled'] ?? 0) !== 1) {
            return false;
        }
        $nextRun = (string) ($settings['next_run_at'] ?? '');
        if ($nextRun === '') {
            return true;
        }
        return strtotime($nextRun) <= time();
    }

    private function tryMysqldump(string $fullPath): bool
    {
        $cmd = sprintf(
            'mysqldump --default-character-set=utf8mb4 --single-transaction --routines --triggers -h%s -P%s -u%s %s %s > %s 2>&1',
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_PORT),
            escapeshellarg(DB_USER),
            DB_PASS !== '' ? '-p' . escapeshellarg(DB_PASS) : '',
            escapeshellarg(DB_NAME),
            escapeshellarg($fullPath)
        );
        @exec($cmd, $output, $code);
        return $code === 0 && is_file($fullPath) && filesize($fullPath) > 0;
    }

    private function runPhpExport(string $fullPath): bool
    {
        $pdo = db();
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
        $sql = "-- Askida Kitap Backup\n";
        $sql .= "-- " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $tableRow) {
            $table = (string) $tableRow[0];
            $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            if (!$create || !isset($create['Create Table'])) {
                continue;
            }
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $create['Create Table'] . ";\n\n";

            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) {
                continue;
            }
            $columns = array_keys($rows[0]);
            $columnSql = '`' . implode('`, `', $columns) . '`';
            foreach ($rows as $row) {
                $values = [];
                foreach ($columns as $column) {
                    $value = $row[$column];
                    $values[] = $value === null ? 'NULL' : $pdo->quote((string) $value);
                }
                $sql .= "INSERT INTO `{$table}` ({$columnSql}) VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return file_put_contents($fullPath, $sql) !== false;
    }

    private function ensureStoragePath(string $relativePath): string
    {
        $basePath = realpath(__DIR__ . '/..');
        if ($basePath === false) {
            throw new RuntimeException('Proje kök dizini çözümlenemedi.');
        }
        $relativePath = trim($relativePath, '/');
        $fullPath = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (!is_dir($fullPath) && !mkdir($fullPath, 0775, true) && !is_dir($fullPath)) {
            throw new RuntimeException('Yedek dizini oluşturulamadı: ' . $fullPath);
        }

        $htaccess = $fullPath . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
        return $fullPath;
    }

    private function nextFromCurrentSettings(): ?string
    {
        $settings = $this->getSettings();
        if ((int) ($settings['automatic_enabled'] ?? 0) !== 1) {
            return null;
        }
        return $this->computeNextRun(
            (string) ($settings['frequency'] ?? 'gunluk'),
            (string) ($settings['backup_time'] ?? '03:00:00'),
            new DateTimeImmutable('now')
        );
    }

    private function computeNextRun(string $frequency, string $backupTime, DateTimeImmutable $from): string
    {
        [$hour, $minute, $second] = array_map('intval', explode(':', $backupTime));
        $candidate = $from->setTime($hour, $minute, $second);
        if ($candidate <= $from) {
            $candidate = match ($frequency) {
                'haftalik' => $candidate->modify('+7 day'),
                'aylik' => $candidate->modify('+1 month'),
                default => $candidate->modify('+1 day'),
            };
        }
        return $candidate->format('Y-m-d H:i:s');
    }

    private function log(?int $backupId, string $action, string $status, string $message): void
    {
        execute_query(
            'INSERT INTO backup_logs (backup_id, action, status, message, created_at)
             VALUES (:backup_id, :action, :status, :message, NOW())',
            [
                'backup_id' => $backupId,
                'action' => $action,
                'status' => $status,
                'message' => mb_substr($message, 0, 255, 'UTF-8'),
            ]
        );
    }
}
