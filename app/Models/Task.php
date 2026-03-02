<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property User $assignee
 * @property User $reviewer
 * @property Project $project
 * @property Collection|Attachment[] $attachments
 */
class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'priority',
        'assignee_id',
        'reviewer_id',
    ];

    public function project() : BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee() : BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function reviewer() : BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function attachments() : HasMany
    {
        return $this->hasMany(Attachment::class, 'task_id');
    }
}
