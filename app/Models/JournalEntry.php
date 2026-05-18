<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\FiscalPeriod;

class JournalEntry extends Model
{
    protected $fillable = [
        'entry_number', 'entry_date', 'description',
        'reference_type', 'reference_id', 'created_by',
        'is_posted', 'posted_at', 'posted_by', 'reversal_of', 'fiscal_period_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'is_posted'  => 'boolean',
        'posted_at'  => 'datetime',
    ];

    public function lines()        { return $this->hasMany(JournalEntryLine::class, 'entry_id'); }
    public function creator()      { return $this->belongsTo(User::class, 'created_by'); }
    public function poster()       { return $this->belongsTo(User::class, 'posted_by'); }
    public function reversalOf()   { return $this->belongsTo(JournalEntry::class, 'reversal_of'); }
    public function reversals()    { return $this->hasMany(JournalEntry::class, 'reversal_of'); }
    public function fiscalPeriod() { return $this->belongsTo(FiscalPeriod::class); }

    public static function boot(): void
    {
        parent::boot();

        // Prevent any update on a posted entry
        static::updating(function (JournalEntry $entry) {
            if ($entry->getOriginal('is_posted')) {
                throw new \DomainException(__('pos.journal_entry_posted_immutable'));
            }
        });

        // Prevent deletion of posted entries
        static::deleting(function (JournalEntry $entry) {
            if ($entry->is_posted) {
                throw new \DomainException(__('pos.journal_entry_posted_immutable'));
            }
        });
    }
}
