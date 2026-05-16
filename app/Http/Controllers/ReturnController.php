<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreReturnRequest;
use App\Models\SalesReturn;
use App\Services\ReturnService;
use App\Traits\ApiResponse;
use App\Traits\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReturnController extends Controller
{
    use ApiResponse, AuditLog;

    public function __construct(private ReturnService $returnService) {}

    public function index() { return view('returns.index'); }

    public function all(Request $request)
    {
        $request->validate([
            'search'   => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:10|max:200',
        ]);

        $query = SalesReturn::with('items')
            ->when($request->search, fn($q, $s) =>
                $q->where('return_number', 'like', "%$s%")
                  ->orWhere('invoice_number', 'like', "%$s%")
                  ->orWhere('customer_name', 'like', "%$s%")
            )
            ->latest();

        $perPage = (int) ($request->per_page ?? 20);
        $returns = $query->paginate($perPage);

        return $this->success([
            'returns'      => $returns->items(),
            'total'        => $returns->total(),
            'current_page' => $returns->currentPage(),
            'last_page'    => $returns->lastPage(),
        ]);
    }

    public function store(StoreReturnRequest $request)
    {
        // FIX-3: إضافة authorization check داخل الـ controller
        // (إضافة طبقة ثانية فوق الـ route middleware)
        $this->authorize('create', SalesReturn::class);

        try {
            $return = $this->returnService->processReturn($request->validated());
            $this->audit('return.created', SalesReturn::class, $return->id, [
                'total'          => $return->total_amount,
                'invoice_id'     => $return->invoice_id,
                'invoice_number' => $return->invoice_number,
            ]);
            return $this->success(['return' => $return], '', 201);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('return.create_db_error', [
                'error'   => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            return $this->error(__('pos.return_creation_failed'), 500);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
