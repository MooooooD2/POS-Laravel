<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = [
        'entry_number', 'entry_date', 'description',
        'reference_type', 'reference_id', 'created_by'
    ];

    protected $casts = ['entry_date' => 'date'];

    public function lines()   { return $this->hasMany(JournalEntryLine::class, 'entry_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
