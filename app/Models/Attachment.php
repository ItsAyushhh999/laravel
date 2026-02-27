<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable=[
        'task_id',
        'file_name',
        'file_path',
    ];
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
