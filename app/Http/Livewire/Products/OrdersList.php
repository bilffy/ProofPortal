<?php

namespace App\Http\Livewire\Products;

use App\Http\Resources\UserResource;
use App\Models\Order;
use Auth;
use Livewire\Component;
use Livewire\WithPagination;

class OrdersList extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount()
    {
        if (!Auth::user()->isSuperAdmin()) {
            return redirect()->route('dashboard');
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $orders = Order::with(['user', 'product', 'status'])
            ->when($this->search !== '', function ($query) {
                $term = '%' . $this->search . '%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('order_number', 'like', $term)
                        ->orWhereHas('user', function ($userQuery) use ($term) {
                            $userQuery->where('firstname', 'like', $term)
                                ->orWhere('lastname', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        })
                        ->orWhereHas('product', function ($productQuery) use ($term) {
                            $productQuery->where('name', 'like', $term);
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.products.orders-list', [
            'orders' => $orders,
        ])->layout('layouts.authenticated', [
            'user' => new UserResource(Auth::user()),
        ]);
    }
}
