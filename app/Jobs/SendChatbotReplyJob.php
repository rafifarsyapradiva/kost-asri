<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\WhatsApp\FonnteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendChatbotReplyJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $target,
        public string $message
    ) {
    }

    public function handle(FonnteService $fonnteService): void
    {
        $fonnteService->send($this->target, $this->message);
    }
}
