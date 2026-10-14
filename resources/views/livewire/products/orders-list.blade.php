<div>
    <div class="py-4 flex items-center justify-between">
        <h3 class="text-2xl">Orders</h3>
        <div class="relative">
            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                <x-icon icon="search"/>
            </div>
            <input
                id="order-search"
                wire:model.live.debounce.300ms="search"
                type="search"
                class="block w-full p-4 py-2 ps-10 text-sm text-gray-900 rounded-lg bg-neutral-300 border-0"
                placeholder="Search order #, name, email, product..."
            />
        </div>
    </div>

    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr>
                    <th scope="col" class="px-4 py-3">Order #</th>
                    <th scope="col" class="px-4 py-3">Product</th>
                    <th scope="col" class="px-4 py-3">Submitted By</th>
                    <th scope="col" class="px-4 py-3">Date</th>
                    <th scope="col" class="px-4 py-3 text-center w-[100px]">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-300 bg-white">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-4 py-3">{{ $order->order_number }}</td>
                        <td class="px-4 py-3">{{ $order->product->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            {{ $order->user->firstname ?? '' }} {{ $order->user->lastname ?? '' }}
                            <span class="text-xs text-neutral block">{{ $order->user->email ?? '' }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $order->created_at?->format('d M Y, g:i a') }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('orders.show', ['order' => $order->id]) }}" class="inline-flex items-center justify-center rounded-md border border-primary px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary-100">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center p-4">No orders yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->onEachSide(1)->links('vendor.livewire.pagination') }}
    </div>
</div>
