<?php

namespace Sultonisky\Slogy\Commands;

use Illuminate\Console\Command;
use Sultonisky\Slogy\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanActivityLogCommand extends Command
{
    protected $signature = 'slogy:clean 
                            {--days= : Override retention days} 
                            {--archive : Archive logs to CSV before deleting}';
    protected $description = 'Clean old activity logs based on retention settings';

    public function handle()
    {
        $archive = $this->option('archive');
        $days = $this->option('days');
        
        $this->info("Starting log cleaning process...");

        $options = ['archive' => $archive];
        if ($days) {
            $options['days'] = (int) $days;
        }

        $count = ActivityLog::clean($options);

        if ($count === 0) {
            $this->info("No old logs found to clean.");
            return 0;
        }

        if ($archive) {
            $this->info("Logs archived successfully to storage/app/slogy/archives/");
        }

        $this->info("Successfully cleaned {$count} old activity logs.");
        return 0;
    }
}
