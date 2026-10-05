<?php

namespace App\Backup;

use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupCollection;
use Spatie\Backup\Tasks\Cleanup\CleanupStrategy;

class KeepLatestThreeBackups extends CleanupStrategy
{
    public function deleteOldBackups(BackupCollection $backups): void
    {
        $backups
            ->sortByDesc(fn (Backup $backup) => $backup->date()->timestamp)
            ->values()
            ->slice(3)
            ->each(fn (Backup $backup) => $backup->delete());
    }
}
