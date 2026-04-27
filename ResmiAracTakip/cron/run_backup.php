<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (!backup_tables_ready()) {
    exit(0);
}

$schedule = backup_schedule();
if (!$schedule || (int) ($schedule['is_active'] ?? 0) !== 1) {
    exit(0);
}

$nextRunAt = (string) ($schedule['next_run_at'] ?? '');
if ($nextRunAt !== '' && strtotime($nextRunAt) > time()) {
    exit(0);
}

run_database_backup(null, 'scheduled');
