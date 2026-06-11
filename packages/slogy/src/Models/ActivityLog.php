<?php

namespace Sultonisky\Slogy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ActivityLog extends Model {

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'model',
        'model_id',
        'old_values',
        'new_values',
        'previous_hash',
        'current_hash',
        'is_genesis',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'is_genesis' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(config('slogy.user_model', 'App\Models\User'));
    }

    public function subject()
    {
        return $this->morphTo('subject', 'model', 'model_id');
    }

    /**
     * Calculate hash for a log entry.
     */
    public static function calculateHash(?string $previousHash, array $data, string $timestamp): string
    {
        $payload = json_encode([
            'previous_hash' => $previousHash,
            'data' => $data,
            'timestamp' => $timestamp,
        ]);

        return hash('sha256', $payload);
    }

    /**
     * Get the last log entry.
     */
    public static function getLastLog(): ?self
    {
        return static::latest('id')->first();
    }

    /**
     * Verify the integrity of the entire log chain.
     */
    public static function verifyChain(): array
    {
        $logs = static::orderBy('id')->get();
        $results = [
            'valid' => true,
            'invalid_logs' => [],
            'total_logs' => $logs->count(),
        ];

        $previousHash = null;

        foreach ($logs as $log) {
            // For genesis block, use its own previous_hash (which might be the last deleted log's hash)
            $currentPreviousHash = $log->is_genesis ? $log->previous_hash : $previousHash;

            $data = [
                'user_id' => $log->user_id,
                'action' => $log->action,
                'description' => $log->description,
                'model' => $log->model,
                'model_id' => $log->model_id,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
            ];

            $timestamp = $log->created_at->toDateTimeString();
            
            $calculatedHash = static::calculateHash($currentPreviousHash, $data, $timestamp);

            if (!hash_equals($calculatedHash, $log->current_hash)) {
                $results['valid'] = false;
                $results['invalid_logs'][] = [
                    'id' => $log->id,
                    'error' => 'Hash mismatch',
                    'expected' => $calculatedHash,
                    'actual' => $log->current_hash,
                ];
            }

            $previousHash = $log->current_hash;
        }

        return $results;
    }

    /**
     * Clean old logs based on various filters with Re-Genesis Block.
     *
     * @param array $options ['days' => int, 'before' => string, 'ids' => array, 'archive' => bool]
     * @return int Number of logs cleaned
     */
    public static function clean(array $options = []): int
    {
        $query = static::query();
        $archive = $options['archive'] ?? false;

        if (isset($options['ids']) && is_array($options['ids'])) {
            $query->whereIn('id', $options['ids']);
        } elseif (isset($options['before'])) {
            $query->where('created_at', '<', $options['before']);
        } else {
            $days = $options['days'] ?? config('slogy.log_retention_days', 60);
            $query->where('created_at', '<', Carbon::now()->subDays($days));
        }

        $logs = $query->orderBy('id')->get();
        $count = $logs->count();

        if ($count === 0) {
            return 0;
        }

        if ($archive) {
            static::exportToJson($logs);
        }

        // Get the last log to be deleted
        $lastDeletedLog = $logs->last();

        // Find the first log to keep and set as new genesis block
        $firstKeptLog = static::where('id', '>', $lastDeletedLog->id)->orderBy('id')->first();

        if ($firstKeptLog) {
            // Re-calculate current hash since we're changing previous_hash
            $data = [
                'user_id' => $firstKeptLog->user_id,
                'action' => $firstKeptLog->action,
                'description' => $firstKeptLog->description,
                'model' => $firstKeptLog->model,
                'model_id' => $firstKeptLog->model_id,
                'old_values' => $firstKeptLog->old_values,
                'new_values' => $firstKeptLog->new_values,
            ];
            
            $newCurrentHash = static::calculateHash(
                $lastDeletedLog->current_hash,
                $data,
                $firstKeptLog->created_at->toDateTimeString()
            );
            
            $firstKeptLog->update([
                'previous_hash' => $lastDeletedLog->current_hash,
                'current_hash' => $newCurrentHash,
                'is_genesis' => true,
            ]);
        }

        // Mass delete for performance
        static::whereIn('id', $logs->pluck('id'))->delete();

        return $count;
    }

    /**
     * Export specific logs to a JSON archive with digital seal.
     * 
     * @param mixed $logs Collection of logs or Query Builder
     * @return string Filename of the generated archive
     */
    public static function exportToJson($logs): string
    {
        if ($logs instanceof \Illuminate\Database\Eloquent\Builder) {
            $logs = $logs->get();
        }

        $logsArray = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'action' => $log->action,
                'description' => $log->description,
                'model' => $log->model,
                'model_id' => $log->model_id,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'previous_hash' => $log->previous_hash,
                'current_hash' => $log->current_hash,
                'is_genesis' => $log->is_genesis,
                'created_at' => $log->created_at->toDateTimeString(),
                'updated_at' => $log->updated_at->toDateTimeString(),
            ];
        })->toArray();

        // Calculate digital seal using HMAC
        $metadata = [
            'exported_at' => now()->toDateTimeString(),
            'log_count' => count($logsArray),
            'version' => '1.0',
        ];

        $dataToSign = json_encode([
            'metadata' => $metadata,
            'logs' => $logsArray,
        ]);

        $digitalSeal = hash_hmac('sha256', $dataToSign, config('app.key'));

        $filename = 'slogy-archive-' . date('Y-m-d-His') . '.json';
        $path = 'slogy/archives/' . $filename;

        $content = json_encode([
            'metadata' => $metadata,
            'logs' => $logsArray,
            'digital_seal' => $digitalSeal,
        ], JSON_PRETTY_PRINT);

        Storage::disk('local')->put($path, $content);

        return $filename;
    }

    /**
     * Validate an uploaded archive file.
     */
    public static function validateArchive(string $filePath): array
    {
        $content = Storage::disk('local')->get($filePath);
        $data = json_decode($content, true);

        if (!isset($data['metadata'], $data['logs'], $data['digital_seal'])) {
            return [
                'valid' => false,
                'error' => 'Invalid archive format',
            ];
        }

        // Recalculate digital seal
        $dataToSign = json_encode([
            'metadata' => $data['metadata'],
            'logs' => $data['logs'],
        ]);

        $calculatedSeal = hash_hmac('sha256', $dataToSign, config('app.key'));

        if (!hash_equals($calculatedSeal, $data['digital_seal'])) {
            return [
                'valid' => false,
                'error' => 'Digital seal verification failed - archive may have been tampered with',
            ];
        }

        // Verify log chain within archive
        $previousHash = null;
        $invalidLogs = [];

        foreach ($data['logs'] as $index => $log) {
            // For genesis block, use its own previous_hash
            $currentPreviousHash = $log['is_genesis'] ? $log['previous_hash'] : $previousHash;

            $logData = [
                'user_id' => $log['user_id'],
                'action' => $log['action'],
                'description' => $log['description'],
                'model' => $log['model'],
                'model_id' => $log['model_id'],
                'old_values' => $log['old_values'],
                'new_values' => $log['new_values'],
            ];

            $calculatedHash = static::calculateHash(
                $currentPreviousHash,
                $logData,
                $log['created_at']
            );

            if (!hash_equals($calculatedHash, $log['current_hash'])) {
                $invalidLogs[] = [
                    'index' => $index,
                    'id' => $log['id'],
                    'error' => 'Hash mismatch',
                ];
            }

            $previousHash = $log['current_hash'];
        }

        if (!empty($invalidLogs)) {
            return [
                'valid' => false,
                'error' => 'Log chain verification failed',
                'invalid_logs' => $invalidLogs,
            ];
        }

        return [
            'valid' => true,
            'metadata' => $data['metadata'],
        ];
    }

    /**
     * Get list of archived files.
     */
    public static function getArchives(): array
    {
        return Storage::disk('local')->files('slogy/archives');
    }
}