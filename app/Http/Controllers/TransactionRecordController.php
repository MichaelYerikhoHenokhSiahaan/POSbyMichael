<?php

namespace App\Http\Controllers;

use App\Models\TransactionRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionRecordController extends Controller
{
    public function index(): View
    {
        $search = request('search');
        $dateFrom = request('date_from');
        $dateTo = request('date_to');
        $sort = request('sort', 'latest');
        $transactionRecordQuery = $this->buildTransactionRecordQuery($search, $dateFrom, $dateTo, $sort);

        $transactionRecords = $transactionRecordQuery
            ->paginate(5)
            ->withQueryString();
        $summary = $this->buildTransactionSummary((clone $transactionRecordQuery)->get());

        $types = TransactionRecord::types();

        return view('transaction-records.index', compact('transactionRecords', 'summary', 'search', 'types', 'dateFrom', 'dateTo', 'sort'));
    }

    public function export(Request $request)
    {
        $search = $request->query('search');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $sort = $request->query('sort', 'latest');

        $transactionRecords = $this->buildTransactionRecordQuery($search, $dateFrom, $dateTo, $sort)
            ->get();
        $summary = $this->buildTransactionSummary($transactionRecords);

        $filename = 'transaction-records-'.now()->format('Ymd_His').'.xls';

        return response()
            ->view('transaction-records.exports.index', compact('transactionRecords', 'summary', 'search', 'dateFrom', 'dateTo', 'sort'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(TransactionRecord::types())],
            'category' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        TransactionRecord::create([
            ...$validated,
            'reference_number' => 'TRX-'.now()->format('YmdHis').'-'.random_int(100, 999),
            'user_id' => $request->user()?->id,
            'transaction_date' => now('Asia/Jakarta'),
        ]);

        return redirect()->route('transaction-records.index')->with('status', 'Transaction recorded successfully.');
    }

    public function edit(TransactionRecord $transactionRecord): View
    {
        $types = TransactionRecord::types();

        return view('transaction-records.edit', compact('transactionRecord', 'types'));
    }

    public function update(Request $request, TransactionRecord $transactionRecord): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(TransactionRecord::types())],
            'category' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $transactionRecord->update($validated);

        return redirect()->route('transaction-records.index')->with('status', 'Transaction updated successfully.');
    }

    public function destroy(TransactionRecord $transactionRecord): RedirectResponse
    {
        $transactionRecord->delete();

        return redirect()->route('transaction-records.index')->with('status', 'Transaction deleted successfully.');
    }

    protected function buildTransactionRecordQuery(?string $search, ?string $dateFrom, ?string $dateTo, string $sort)
    {
        return TransactionRecord::query()
            ->with('user')
            ->when($search, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('reference_number', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('payment_method', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        });
                });
            })
            ->when($dateFrom, function ($query, $dateFrom) {
                $query->whereDate('transaction_date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query, $dateTo) {
                $query->whereDate('transaction_date', '<=', $dateTo);
            })
            ->orderBy('transaction_date', $sort === 'oldest' ? 'asc' : 'desc');
    }

    protected function buildTransactionSummary($transactionRecords): array
    {
        $debit = $transactionRecords->sum(function (TransactionRecord $transactionRecord) {
            return $transactionRecord->type === TransactionRecord::TYPE_INCOME
                ? (float) $transactionRecord->amount
                : 0;
        });

        $credit = $transactionRecords->sum(function (TransactionRecord $transactionRecord) {
            return $transactionRecord->type === TransactionRecord::TYPE_EXPENSE
                ? (float) $transactionRecord->amount
                : 0;
        });

        return [
            'debit' => $debit,
            'credit' => $credit,
            'gain' => $debit - $credit,
        ];
    }
}
