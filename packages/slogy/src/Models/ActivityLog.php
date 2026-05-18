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
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
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
     * Clean old logs based on various filters.
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
            $days = $options['days'] ?? config('slogy.log_retention_days', 30);
            $query->where('created_at', '<', Carbon::now()->subDays($days));
        }

        $logs = $query->get();
        $count = $logs->count();

        if ($count === 0) {
            return 0;
        }

        if ($archive) {
            static::export($logs);
        }

        // Mass delete for performance
        static::whereIn('id', $logs->pluck('id'))->delete();

        return $count;
    }

    /**
     * Export specific logs to a CSV archive without deleting them.
     * 
     * @param mixed $logs Collection of logs or Query Builder
     * @return string Filename of the generated archive
     */
    public static function export($logs): string
    {
        if ($logs instanceof \Illuminate\Database\Eloquent\Builder) {
            $logs = $logs->get();
        }

        $filename = 'slogy-export-' . date('Y-m-d-His') . '.csv';
        $path = 'slogy/archives/' . $filename;

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['ID', 'User ID', 'Action', 'Description', 'Model', 'Model ID', 'Old Values', 'New Values', 'Created At']);

        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->id,
                $log->user_id,
                $log->action,
                $log->description,
                $log->model,
                $log->model_id,
                json_encode($log->old_values),
                json_encode($log->new_values),
                $log->created_at,
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        Storage::disk('local')->put($path, $content);

        return $filename;
    }

    /**
     * Get list of archived files.
     */
    public static function getArchives(): array
    {
        return Storage::disk('local')->files('slogy/archives');
    }
}