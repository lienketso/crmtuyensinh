<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $table = 'tasks';
    protected $fillable = [
        'lead_id',
        'user_id',
        'title',
        'description',
        'due_at',
        'status'
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
                     ->where('due_at', '<', now());
    }

    public function scopeToday($query)
    {
        return $query->whereDate('due_at', today());
    }
}
