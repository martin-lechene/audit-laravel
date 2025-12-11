<?php

namespace MartinLechene\AuditSuite\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $project_name
 * @property string $environment
 * @property \DateTime $started_at
 * @property \DateTime|null $completed_at
 * @property string $status (pending, running, completed, failed)
 * @property int $total_findings
 * @property float $overall_score
 * @property array $findings_by_severity
 * @property array $findings_by_category
 * @property \Illuminate\Database\Eloquent\Collection $findings
 */
class AuditSession extends Model
{
    protected $table = 'audit_sessions';

    protected $fillable = [
        'project_name',
        'environment',
        'started_at',
        'completed_at',
        'status',
        'total_findings',
        'overall_score',
        'findings_by_severity',
        'findings_by_category',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'findings_by_severity' => 'array',
        'findings_by_category' => 'array',
        'overall_score' => 'float',
    ];

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public function findingsBySeverity(string $severity)
    {
        return $this->findings()->where('severity', $severity)->get();
    }

    public function findingsByCategory(string $category)
    {
        return $this->findings()->where('category', $category)->get();
    }
}

