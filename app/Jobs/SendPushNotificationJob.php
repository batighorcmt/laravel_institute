<?php

namespace App\Jobs;

use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $tokens,
        protected string $title,
        protected string $body,
        protected array $data = []
    ) {}

    public function handle(FcmService $fcm): void
    {
        foreach ($this->tokens as $token) {
            $fcm->sendToToken($token, $this->title, $this->body, $this->data);
        }
    }
}
