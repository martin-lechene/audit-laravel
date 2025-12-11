<?php

namespace MartinLechene\AuditSuite\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $audit_session_id
 * @property string $category (seo, security, performance, ...)
 * @property string $rule_name
 * @property string $severity (critical, high, medium, low, info)
 * @property string $title
 * @property string $description
 * @property array $affected_items
 * @property string|null $fix_suggestion
 * @property array|null $evidence (JSON)
 * @property float $score
 * @property \DateTime $created_at
 */
class Finding extends Model
{
    protected $fillable = [
        'audit_session_id',
        'category',
        'rule_name',
        'severity',
        'title',
        'description',
        'affected_items',
        'fix_suggestion',
        'evidence',
        'score',
    ];

    protected $casts = [
        'affected_items' => 'array',
        'evidence' => 'array',
        'created_at' => 'datetime',
        'score' => 'float',
    ];

    public function auditSession(): BelongsTo
    {
        return $this->belongsTo(AuditSession::class);
    }
}

