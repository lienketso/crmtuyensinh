<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    protected $fillable = [
        'school_id',
        'assigned_to',
        'name',
        'phone',
        'email',
        'interest_course',
        'source',
        'status',
        'year_of_admission',
    ];

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function admissionProfile(): HasOne
    {
        return $this->hasOne(AdmissionProfile::class);
    }
}
