<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponse;

    public function __construct(private CustomerService $service) {}

    public function index()
    {
        return view('customers.index');
    }

    public function all(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%$s%")
                      ->orWhere('phone', 'like', "%$s%")
                      ->orWhere('code', 'like', "%$s%");
                });
            })
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when(! $request->boolean('with_inactive'), fn($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate($request->per_page ?? 20);

        return $this->success($customers->toArray());
    }

    public function search(Request $request): JsonResponse
    {
        $q = $request->get('q', '');

        $customers = Customer::where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%$q%")
                      ->orWhere('phone', 'like', "%$q%")
                      ->orWhere('code', 'like', "%$q%");
            })
            ->select('id', 'code', 'name', 'phone', 'type', 'balance', 'loyalty_points')
            ->orderBy('name')
            ->limit(10)
            ->get();

        return $this->success(['customers' => $customers]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                => 'required|string|max:150',
            'phone'               => 'nullable|string|max:20',
            'email'               => 'nullable|email|max:150',
            'type'                => 'nullable|in:individual,business',
            'national_id'         => 'nullable|string|max:14',
            'tax_number'          => 'nullable|string|max:20',
            'commercial_register' => 'nullable|string|max:30',
            'governate'           => 'nullable|string|max:50',
            'city'                => 'nullable|string|max:100',
            'address'             => 'nullable|string|max:500',
            'credit_limit'        => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string|max:500',
            'is_active'           => 'boolean',
        ]);

        $data['code'] = $this->service->nextCode();
        $data['type'] = $data['type'] ?? 'individual';

        $customer = Customer::create($data);

        return $this->success(['customer' => $customer], '', 201);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $data = $request->validate([
            'name'                => 'sometimes|string|max:150',
            'phone'               => 'nullable|string|max:20',
            'email'               => 'nullable|email|max:150',
            'type'                => 'nullable|in:individual,business',
            'national_id'         => 'nullable|string|max:14',
            'tax_number'          => 'nullable|string|max:20',
            'commercial_register' => 'nullable|string|max:30',
            'governate'           => 'nullable|string|max:50',
            'city'                => 'nullable|string|max:100',
            'address'             => 'nullable|string|max:500',
            'credit_limit'        => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string|max:500',
            'is_active'           => 'boolean',
        ]);

        $customer->update($data);

        return $this->success(['customer' => $customer->fresh()]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        if ($customer->invoices()->exists()) {
            return $this->error(__('pos.customer_has_invoices'), 422);
        }

        $customer->delete();

        return $this->success([], __('pos.customer_deleted'));
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load('accountEntries');
        return $this->success(['customer' => $customer]);
    }
}
