<?php

namespace App\Console\Commands;

use App\Models\SmsMessage;
use Illuminate\Console\Command;

class ResetStuckSmsJobs extends Command
{
    protected $signature = 'sms:reset-stuck';

    protected $description = 'Reset SMS jobs stuck in processing status for more than 5 minutes';

    public function handle(): int
    {
        $stuckTime = now()->subMinutes(5);

        $stuckJobs = SmsMessage::where('status', 'processing')
            ->where('updated_at', '<=', $stuckTime)
            ->get();

        $resetCount = 0;
        $failedCount = 0;

        foreach ($stuckJobs as $job) {
            if ($job->attempts >= 3) {
                $job->update([
                    'status' => 'failed',
                    'error_message' => 'Job timed out in processing status after maximum attempts (3).',
                ]);
                $failedCount++;
            } else {
                $job->update([
                    'status' => 'pending',
                    'gateway_id' => null,
                ]);
                $resetCount++;
            }
        }

        $this->info("Stuck SMS jobs processed: {$resetCount} reset to pending, {$failedCount} marked as failed.");

        return Command::SUCCESS;
    }
}
