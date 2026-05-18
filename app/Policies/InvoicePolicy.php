<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Invoice;

class InvoicePolicy
{
    public function viewAny(User $user): bool { return $user->can('view_pos'); }
    public function view(User $user, Invoice $i): bool { return $user->can('view_pos'); }
    public function create(User $user): bool  { return $user->can('view_pos'); }
    public function cancel(User $user, Invoice $invoice): bool
    {
        return $user->can('view_warehouse') || $user->hasRole('admin');
    }
}
