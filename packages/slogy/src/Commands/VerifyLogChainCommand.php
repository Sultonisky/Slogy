<?php

namespace Sultonisky\Slogy\Commands;

use Illuminate\Console\Command;
use Sultonisky\Slogy\Models\ActivityLog;

class VerifyLogChainCommand extends Command
{
    protected $signature = 'slogy:verify';
    protected $description = 'Verify the integrity of the activity log chain';

    public function handle()
    {
        $this->info('Verifying activity log chain integrity...');

        $result = ActivityLog::verifyChain();

        $this->newLine();
        $this->line("Total logs checked: {$result['total_logs']}");

        if ($result['valid']) {
            $this->newLine();
            $this->info('✅ Log chain is valid! All logs are intact and untampered.');
            return 0;
        } else {
            $this->newLine();
            $this->error('❌ Log chain verification failed!');
            $this->newLine();
            
            foreach ($result['invalid_logs'] as $invalidLog) {
                $this->error("  - Log ID {$invalidLog['id']}: {$invalidLog['error']}");
                $this->line("    Expected: {$invalidLog['expected']}");
                $this->line("    Actual: {$invalidLog['actual']}");
                $this->newLine();
            }
            
            return 1;
        }
    }
}
