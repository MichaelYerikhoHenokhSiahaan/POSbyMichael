<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Transaction Records Export</title>
    </head>
    <body>
        <table border="1">
            <tr>
                <td colspan="9">Transaction Records Export</td>
            </tr>
            <tr>
                <td colspan="9">Search: {{ $search ?: 'All transaction records' }}</td>
            </tr>
            <tr>
                <td colspan="9">Date range: {{ $dateFrom ?: 'Start' }} - {{ $dateTo ?: 'End' }}</td>
            </tr>
            <tr>
                <td colspan="9">Sort: {{ str($sort)->headline() }}</td>
            </tr>
            <tr>
                <td colspan="9">Debit total: {{ number_format($summary['debit'], 2, '.', '') }} | Credit total: {{ number_format($summary['credit'], 2, '.', '') }} | Gain: {{ number_format($summary['gain'], 2, '.', '') }}</td>
            </tr>
            <tr>
                <th>Reference</th>
                <th>Date</th>
                <th>Type</th>
                <th>Category</th>
                <th>Payment method</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Recorded by</th>
                <th>Notes</th>
            </tr>
            @foreach ($transactionRecords as $transactionRecord)
                <tr>
                    <td>{{ $transactionRecord->reference_number }}</td>
                    <td>{{ $transactionRecord->transaction_date->format('Y-m-d H:i:s') }}</td>
                    <td>{{ str($transactionRecord->type)->headline() }}</td>
                    <td>{{ $transactionRecord->category }}</td>
                    <td>{{ str($transactionRecord->payment_method)->headline() }}</td>
                    <td>{{ $transactionRecord->type === \App\Models\TransactionRecord::TYPE_INCOME ? number_format($transactionRecord->amount, 2, '.', '') : '' }}</td>
                    <td>{{ $transactionRecord->type === \App\Models\TransactionRecord::TYPE_EXPENSE ? number_format($transactionRecord->amount, 2, '.', '') : '' }}</td>
                    <td>{{ $transactionRecord->user?->name ?? $transactionRecord->user?->username ?? 'Unknown' }}</td>
                    <td>{{ $transactionRecord->notes }}</td>
                </tr>
            @endforeach
        </table>
    </body>
</html>
