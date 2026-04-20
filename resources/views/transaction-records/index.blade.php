@extends('layouts.app')

@section('content')
    <div class="auto-layout-grid">
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Record transaction</h2>
            <p class="mt-1 text-sm text-slate-500">Log income and expense activity outside POS sales.</p>

            <form action="{{ route('transaction-records.store') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="type" class="text-sm font-medium text-slate-700">Type</label>
                    <select id="type" name="type" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                        @foreach ($types as $type)
                            <option value="{{ $type }}" @selected(old('type', \App\Models\TransactionRecord::TYPE_INCOME) === $type)>{{ str($type)->headline() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="category" class="text-sm font-medium text-slate-700">Category</label>
                    <input id="category" name="category" type="text" value="{{ old('category') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                </div>
                <div>
                    <label for="amount" class="text-sm font-medium text-slate-700">Amount</label>
                    <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                </div>
                <div>
                    <label for="payment_method" class="text-sm font-medium text-slate-700">Payment method</label>
                    <select id="payment_method" name="payment_method" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                        @foreach (['cash', 'card', 'transfer', 'e-wallet'] as $method)
                            <option value="{{ $method }}" @selected(old('payment_method', 'cash') === $method)>{{ str($method)->headline() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-700">Transaction date</p>
                    <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        Automatically recorded using Jakarta time when you save this transaction.
                    </div>
                </div>
                <div>
                    <label for="notes" class="text-sm font-medium text-slate-700">Notes</label>
                    <textarea id="notes" name="notes" rows="4" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Save transaction</button>
            </form>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="auto-layout-header">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Transaction records</h2>
                    <p class="mt-1 text-sm text-slate-500">Review income and expense entries with their recording details.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">
                    {{ $transactionRecords->total() }} total
                </span>
            </div>

            <form action="{{ route('transaction-records.index') }}" method="GET" class="auto-layout-filters mt-6">
                <div>
                    <label for="transaction-search" class="text-sm font-medium text-slate-700">Search transactions</label>
                    <input id="transaction-search" name="search" type="text" value="{{ $search }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none" placeholder="Search by reference, category, type, or user">
                </div>
                <div>
                    <label for="date_from" class="text-sm font-medium text-slate-700">From date</label>
                    <input id="date_from" name="date_from" type="date" value="{{ $dateFrom }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="date_to" class="text-sm font-medium text-slate-700">To date</label>
                    <input id="date_to" name="date_to" type="date" value="{{ $dateTo }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label for="sort" class="text-sm font-medium text-slate-700">Sort</label>
                    <select id="sort" name="sort" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none">
                        <option value="latest" @selected($sort === 'latest')>Latest</option>
                        <option value="oldest" @selected($sort === 'oldest')>Oldest</option>
                    </select>
                </div>
                <div class="auto-layout-actions">
                    <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Search</button>
                    <button type="submit" formaction="{{ route('transaction-records.export') }}" class="rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">Export Excel</button>
                    @if ($search || $dateFrom || $dateTo || $sort !== 'latest')
                        <a href="{{ route('transaction-records.index') }}" class="rounded-full bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700">Clear</a>
                    @endif
                </div>
            </form>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                    <p class="text-sm font-medium text-emerald-700">Debit</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-900">Rp {{ number_format($summary['debit'], 2) }}</p>
                    <p class="mt-1 text-xs uppercase tracking-[0.2em] text-emerald-600">Income total</p>
                </div>
                <div class="rounded-3xl bg-rose-50 p-4 ring-1 ring-rose-100">
                    <p class="text-sm font-medium text-rose-700">Credit</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-900">Rp {{ number_format($summary['credit'], 2) }}</p>
                    <p class="mt-1 text-xs uppercase tracking-[0.2em] text-rose-600">Expense total</p>
                </div>
                <div class="rounded-3xl bg-slate-900 p-4 text-white">
                    <p class="text-sm font-medium text-slate-300">Gain</p>
                    <p class="mt-2 text-2xl font-semibold">Rp {{ number_format($summary['gain'], 2) }}</p>
                    <p class="mt-1 text-xs uppercase tracking-[0.2em] text-slate-400">Debit minus credit</p>
                </div>
            </div>

            <div class="mt-6 space-y-4">
                @forelse ($transactionRecords as $transactionRecord)
                    <div class="rounded-3xl border border-slate-200 p-5">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-semibold text-slate-900">{{ $transactionRecord->reference_number }}</h3>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] {{ $transactionRecord->type === \App\Models\TransactionRecord::TYPE_INCOME ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $transactionRecord->type }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm font-medium text-slate-700">{{ $transactionRecord->category }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ str($transactionRecord->payment_method)->headline() }} • {{ $transactionRecord->transaction_date->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</p>
                                <p class="mt-1 text-sm text-slate-500">Recorded by {{ $transactionRecord->user?->username ?? 'Unknown' }}</p>
                                <p class="mt-3 text-sm text-slate-500">{{ $transactionRecord->notes ?: 'No notes added.' }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-3">
                                <div class="rounded-2xl px-4 py-3 text-right {{ $transactionRecord->type === \App\Models\TransactionRecord::TYPE_INCOME ? 'bg-emerald-50' : 'bg-rose-50' }}">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] {{ $transactionRecord->type === \App\Models\TransactionRecord::TYPE_INCOME ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ $transactionRecord->type === \App\Models\TransactionRecord::TYPE_INCOME ? 'Debit' : 'Credit' }}
                                    </p>
                                    <p class="mt-1 text-lg font-semibold {{ $transactionRecord->type === \App\Models\TransactionRecord::TYPE_INCOME ? 'text-emerald-600' : 'text-rose-600' }}">
                                        Rp {{ number_format($transactionRecord->amount, 2) }}
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('transaction-records.edit', $transactionRecord) }}" class="rounded-full bg-amber-100 px-4 py-2 text-sm font-medium text-amber-700">Edit</a>
                                    <form action="{{ route('transaction-records.destroy', $transactionRecord) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-full bg-rose-100 px-4 py-2 text-sm font-medium text-rose-700">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                        No transaction records have been created yet.
                    </div>
                @endforelse
            </div>

            @if ($transactionRecords->hasPages())
                <div class="mt-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <p class="text-sm text-slate-500">
                        Showing {{ $transactionRecords->firstItem() }}-{{ $transactionRecords->lastItem() }} of {{ $transactionRecords->total() }} records
                    </p>
                    <div>
                        {{ $transactionRecords->onEachSide(1)->links() }}
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
