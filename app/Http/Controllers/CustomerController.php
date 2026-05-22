<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
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

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $data         = $request->validated();
        $data['code'] = $this->service->nextCode();
        $data['type'] = $data['type'] ?? 'individual';

        $customer = Customer::create($data);

        return $this->success(['customer' => new CustomerResource($customer)], '', 201);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer->update($request->validated());

        return $this->success(['customer' => new CustomerResource($customer->fresh())]);
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
        return $this->success(['customer' => new CustomerResource($customer)]);
    }
}
