<?php

namespace App\Jobs;

use App\Models\SmsMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SmsMessage $smsMessage
    ) {}

    public function handle(): void
    {
        if ($this->smsMessage->status === 'pending') {
            // Retain pending status for gateway polling
        }
    }
}
