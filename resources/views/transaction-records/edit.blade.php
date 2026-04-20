@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-4xl rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Edit transaction</h2>
                <p class="mt-1 text-sm text-slate-500">Update the recorded details for {{ $transactionRecord->reference_number }}.</p>
            </div>
            <a href="{{ route('transaction-records.index') }}" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700">Back</a>
        </div>

        <form action="{{ route('transaction-records.update', $transactionRecord) }}" method="POST" class="mt-6 grid gap-4 md:grid-cols-2">
            @csrf
            @method('PUT')
            <div>
                <label for="type" class="text-sm font-medium text-slate-700">Type</label>
                <select id="type" name="type" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(old('type', $transactionRecord->type) === $type)>{{ str($type)->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="payment_method" class="text-sm font-medium text-slate-700">Payment method</label>
                <select id="payment_method" name="payment_method" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                    @foreach (['cash', 'card', 'transfer', 'e-wallet'] as $method)
                        <option value="{{ $method }}" @selected(old('payment_method', $transactionRecord->payment_method) === $method)>{{ str($method)->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="category" class="text-sm font-medium text-slate-700">Category</label>
                <input id="category" name="category" type="text" value="{{ old('category', $transactionRecord->category) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
            </div>
            <div>
                <label for="amount" class="text-sm font-medium text-slate-700">Amount</label>
                <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount', $transactionRecord->amount) }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
            </div>
            <div class="md:col-span-2">
                <p class="text-sm font-medium text-slate-700">Transaction date</p>
                <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    {{ $transactionRecord->transaction_date->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB
                </div>
            </div>
            <div class="md:col-span-2">
                <label for="notes" class="text-sm font-medium text-slate-700">Notes</label>
                <textarea id="notes" name="notes" rows="4" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">{{ old('notes', $transactionRecord->notes) }}</textarea>
            </div>
            <button type="submit" class="md:col-span-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white">Update transaction</button>
        </form>
    </div>
@endsection
