<?php

namespace MartinLechene\AuditSuite\Events;

use MartinLechene\AuditSuite\Models\AuditSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuditFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AuditSession $session,
        public \Throwable $exception
    ) {}
}

