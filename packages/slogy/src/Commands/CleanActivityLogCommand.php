<?php

namespace Sultonisky\Slogy\Commands;

use Illuminate\Console\Command;
use Sultonisky\Slogy\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanActivityLogCommand extends Command
{
    protected $signature = 'slogy:clean {--archive : Archive logs to CSV before deleting}';
    protected $description = 'Clean old activity logs based on retention settings';

    public function handle()
    {
        $days = config('slogy.log_retention_days', 30);
        $cutOffDate = Carbon::now()->subDays($days);

        $this->info("Cleaning logs older than {$cutOffDate->toDateTimeString()} ({$days} days)");

        // Make sure we are using the correct connection for the query
        $query = ActivityLog::query()->where('created_at', '<', $cutOffDate);
        $count = $query->count();

        if ($count === 0) {
            $this->info("No old logs found to clean.");
            return 0;
        }

        if ($this->option('archive')) {
            $this->archiveLogs($query->get());
        }

        $query->delete();

        $this->info("Successfully cleaned {$count} old activity logs.");
        return 0;
    }

    protected function archiveLogs($logs)
    {
        $filename = 'slogy-archive-' . date('Y-m-d-His') . '.csv';
        $path = 'slogy/archives/' . $filename;

        $handle = fopen('php://temp', 'r+');
        
        // CSV Header
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
        
        $this->info("Logs archived successfully to: storage/app/{$path}");
    }
}
