@extends('layouts.app')

@section('content')
    <div class="grid gap-8 xl:grid-cols-[0.95fr_1.05fr]">
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Add customer</h2>
            <p class="mt-1 text-sm text-slate-500">Store buyer details for repeat orders and receipts.</p>

            <form action="{{ route('customers.store') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="name" class="text-sm font-medium text-slate-700">Customer name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                </div>
                <div>
                    <label for="email" class="text-sm font-medium text-slate-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="phone" class="text-sm font-medium text-slate-700">Phone</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="address" class="text-sm font-medium text-slate-700">Address</label>
                    <textarea id="address" name="address" rows="4" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">{{ old('address') }}</textarea>
                </div>
                <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Save customer</button>
            </form>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Customer list</h2>
                    <p class="mt-1 text-sm text-slate-500">View and update registered buyers.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">
                    {{ $customers->count() }} customers
                </span>
            </div>

            <div class="mt-6 space-y-4">
                @forelse ($customers as $customer)
                    <div class="rounded-3xl border border-slate-200 p-5">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">{{ $customer->name }}</h3>
                                <div class="mt-2 space-y-1 text-sm text-slate-500">
                                    <p>{{ $customer->email ?: 'No email' }}</p>
                                    <p>{{ $customer->phone ?: 'No phone' }}</p>
                                    <p>{{ $customer->address ?: 'No address' }}</p>
                                </div>
                                <p class="mt-3 text-xs font-medium uppercase tracking-[0.25em] text-slate-400">{{ $customer->sales_count }} sales</p>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('customers.edit', $customer) }}" class="rounded-full bg-amber-100 px-4 py-2 text-sm font-medium text-amber-700">Edit</a>
                                <form action="{{ route('customers.destroy', $customer) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-full bg-rose-100 px-4 py-2 text-sm font-medium text-rose-700">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                        No customers have been created yet.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
