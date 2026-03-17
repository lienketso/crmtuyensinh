<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionProfile extends Model
{
    protected $table = 'admission_profiles';

    protected $fillable = [
        'lead_id',
        'identification_number',
        'province',
        'graduation_year',
        'academic_level',
        'gpa',
        'academic_records',
        'document_status',
        'admin_note',
        'admission_file',
        'admission_method',
    ];

    protected $casts = [
        'academic_records' => 'array',
        'graduation_year' => 'integer',
        'gpa' => 'decimal:2',
    ];
    
    public function lead(): BelongsTo
    {
        // Giả sử khóa ngoại là lead_id trỏ đến id của bảng leads
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
