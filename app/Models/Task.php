<?php

namespace App\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Task extends Model
{
    use SoftDeletes, HasFactory;
    protected $fillable=[
        'project_id',
        'title',
        'description',
        'priority',
        'assignee_id',
        'reviewer_id'
    ];
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'task_id');
    }
}
