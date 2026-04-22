<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntryLine extends Model
{
    protected $fillable = ['entry_id', 'account_id', 'debit', 'credit', 'description'];

    protected $casts = ['debit' => 'float', 'credit' => 'float'];

    public function entry()   { return $this->belongsTo(JournalEntry::class, 'entry_id'); }
    public function account() { return $this->belongsTo(Account::class); }
}
