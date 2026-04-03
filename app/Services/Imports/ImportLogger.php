<?php

namespace App\Services\Imports;

use App\Models\ImportLog;
use Carbon\Carbon;

class ImportLogger
{
    public function start(string $entity, ?string $dateFrom = null, ?string $dateTo = null): ImportLog
    {
        return ImportLog::create([
            'entity' => $entity,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status' => 'started',
            'started_at' => Carbon::now(),
        ]);
    }

    public function success(ImportLog $log, array $result): void
    {
        $log->update([
            'status' => 'success',
            'processed' => $result['processed'] ?? 0,
            'created' => $result['created'] ?? 0,
            'updated' => $result['updated'] ?? 0,
            'unchanged' => $result['unchanged'] ?? 0,
            'last_page' => $result['last_page'] ?? 0,
            'finished_at' => Carbon::now(),
        ]);
    }

    public function failed(ImportLog $log, \Throwable $e, array $partial = []): void
    {
        $log->update([
            'status' => 'failed',
            'processed' => $partial['processed'] ?? 0,
            'created' => $partial['created'] ?? 0,
            'updated' => $partial['updated'] ?? 0,
            'unchanged' => $partial['unchanged'] ?? 0,
            'last_page' => $partial['last_page'] ?? 0,
            'error_message' => mb_substr($e->getMessage(), 0, 65000),
            'finished_at' => Carbon::now(),
        ]);
    }
}
