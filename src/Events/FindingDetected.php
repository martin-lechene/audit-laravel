<?php

namespace MartinLechene\AuditSuite\Events;

use MartinLechene\AuditSuite\Models\AuditSession;
use MartinLechene\AuditSuite\Models\Finding;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FindingDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Finding $finding,
        public AuditSession $session
    ) {}
}

