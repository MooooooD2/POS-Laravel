<?php
// =============================================================
// ALL MODELS - جميع الموديلات
// =============================================================

// ---------------------------------------------------------------
// FILE: app/Models/User.php
// ---------------------------------------------------------------
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['username', 'password', 'full_name', 'role', 'is_active'];
    protected $hidden   = ['password', 'remember_token'];

    protected $casts = ['is_active' => 'boolean'];

    public function getAuthIdentifierName() { return 'username'; }

    public function invoices()      { return $this->hasMany(Invoice::class, 'cashier_id'); }
    public function stockMovements(){ return $this->hasMany(StockMovement::class, 'employee_id'); }
    public function purchaseOrders(){ return $this->hasMany(PurchaseOrder::class, 'created_by'); }
}

// ---------------------------------------------------------------
// FILE: app/Models/Product.php
// ---------------------------------------------------------------
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'price', 'cost_price', 'quantity',
        'min_stock', 'barcode', 'category', 'supplier'
    ];

    protected $casts = ['price' => 'float', 'cost_price' => 'float'];

    public function getLowStockAttribute(): bool
    {
        return $this->quantity <= $this->min_stock;
    }

    public function invoiceItems()  { return $this->hasMany(InvoiceItem::class); }
    public function stockMovements(){ return $this->hasMany(StockMovement::class); }
}

// ---------------------------------------------------------------
// FILE: app/Models/Invoice.php
// ---------------------------------------------------------------
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number', 'total', 'discount',
        'final_total', 'payment_method', 'cashier_id', 'cashier_name', 'status'
    ];

    protected $casts = ['total' => 'float', 'final_total' => 'float', 'discount' => 'float'];

    public function items()   { return $this->hasMany(InvoiceItem::class); }
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id'); }
    public function returns() { return $this->hasMany(SalesReturn::class); }
}

// ---------------------------------------------------------------
// FILE: app/Models/InvoiceItem.php
// ---------------------------------------------------------------
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = ['invoice_id', 'product_id', 'product_name', 'quantity', 'price', 'subtotal'];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function product() { return $this->belongsTo(Product::class); }
}

// ---------------------------------------------------------------
// FILE: app/Models/StockMovement.php
// ---------------------------------------------------------------
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id', 'product_name', 'quantity',
        'movement_type', 'reason', 'reference_id',
        'employee_id', 'employee_name'
    ];

    public function product()  { return $this->belongsTo(Product::class); }
    public function employee() { return $this->belongsTo(User::class, 'employee_id'); }
}

// ---------------------------------------------------------------
// FILE: app/Models/Supplier.php
// ---------------------------------------------------------------
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['name', 'phone', 'address', 'email'];

    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class); }
    public function payments()       { return $this->hasMany(SupplierPayment::class); }
    public function accounts()       { return $this->hasMany(SupplierAccount::class); }
}

// ---------------------------------------------------------------
// FILE: app/Models/PurchaseOrder.php
// ---------------------------------------------------------------
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number', 'supplier_id', 'supplier_name',
        'total_amount', 'discount', 'final_amount',
        'status', 'order_date', 'expected_date',
        'received_date', 'notes', 'created_by', 'created_by_name'
    ];

    protected $casts = ['order_date' => 'date', 'expected_date' => 'date', 'received_date' => 'date'];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function items()    { return $this->hasMany(PurchaseOrderItem::class, 'po_id'); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
}

// ---------------------------------------------------------------
// FILE: app/Models/PurchaseOrderItem.php
// ---------------------------------------------------------------
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'po_number', 'product_id', 'product_name',
        'quantity', 'cost_price', 'selling_price',
        'subtotal', 'received_quantity'
    ];

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class, 'po_id'); }
    public function product()       { return $this->belongsTo(Product::class); }
}

// ---------------------------------------------------------------
// FILE: app/Models/SupplierPayment.php
// ---------------------------------------------------------------
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    protected $fillable = [
        'payment_number', 'supplier_id', 'supplier_name',
        'amount', 'payment_method', 'payment_date',
        'notes', 'created_by', 'created_by_name'
    ];

    protected $casts = ['payment_date' => 'date', 'amount' => 'float'];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
}

// ---------------------------------------------------------------
// FILE: app/Models/SupplierAccount.php
// ---------------------------------------------------------------
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierAccount extends Model
{
    protected $fillable = [
        'supplier_id', 'transaction_type', 'reference_id',
        'reference_number', 'debit', 'credit', 'balance',
        'notes', 'created_by'
    ];

    protected $casts = ['debit' => 'float', 'credit' => 'float', 'balance' => 'float'];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
}

// ---------------------------------------------------------------
// FILE: app/Models/Account.php
// ---------------------------------------------------------------
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['account_code', 'account_name', 'account_type', 'parent_id', 'balance', 'description'];

    protected $casts = ['balance' => 'float'];

    public function parent()   { return $this->belongsTo(Account::class, 'parent_id'); }
    public function children() { return $this->hasMany(Account::class, 'parent_id'); }
    public function lines()    { return $this->hasMany(JournalEntryLine::class); }
}

// ---------------------------------------------------------------
// FILE: app/Models/JournalEntry.php
// ---------------------------------------------------------------
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

// ---------------------------------------------------------------
// FILE: app/Models/JournalEntryLine.php
// ---------------------------------------------------------------
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntryLine extends Model
{
    protected $fillable = ['entry_id', 'account_id', 'debit', 'credit', 'description'];

    protected $casts = ['debit' => 'float', 'credit' => 'float'];

    public function entry()   { return $this->belongsTo(JournalEntry::class, 'entry_id'); }
    public function account() { return $this->belongsTo(Account::class); }
}

// ---------------------------------------------------------------
// FILE: app/Models/SalesReturn.php
// ---------------------------------------------------------------
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    protected $fillable = [
        'return_number', 'invoice_id', 'invoice_number',
        'customer_name', 'total_amount', 'reason',
        'status', 'return_date', 'processed_by', 'processed_by_name'
    ];

    protected $casts = ['return_date' => 'date', 'total_amount' => 'float'];

    public function invoice()   { return $this->belongsTo(Invoice::class); }
    public function items()     { return $this->hasMany(ReturnItem::class, 'return_id'); }
    public function processor() { return $this->belongsTo(User::class, 'processed_by'); }
}

// ---------------------------------------------------------------
// FILE: app/Models/ReturnItem.php
// ---------------------------------------------------------------
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    protected $fillable = ['return_id', 'product_id', 'product_name', 'quantity', 'price', 'subtotal'];

    public function salesReturn() { return $this->belongsTo(SalesReturn::class, 'return_id'); }
    public function product()     { return $this->belongsTo(Product::class); }
}
