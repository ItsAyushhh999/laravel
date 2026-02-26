<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use SoftDeletes;
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
