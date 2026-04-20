<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Recent Transactions Export</title>
    </head>
    <body>
        <table border="1">
            <tr>
                <td colspan="13">Recent Transactions Export</td>
            </tr>
            <tr>
                <td colspan="13">Search: {{ $search ?: 'All products' }}</td>
            </tr>
            <tr>
                <td colspan="13">Category: {{ $categoryName ?: 'All categories' }}</td>
            </tr>
            <tr>
                <td colspan="13">Date range: {{ $dateFrom ?: 'Start' }} - {{ $dateTo ?: 'End' }}</td>
            </tr>
            <tr>
                <td colspan="13">Sort: {{ str($sort)->headline() }}</td>
            </tr>
            <tr>
                <th>Invoice</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Cashier</th>
                <th>Categories</th>
                <th>Products</th>
                <th>Purchase details</th>
                <th>Payment method</th>
                <th>Subtotal</th>
                <th>Discount</th>
                <th>Total</th>
                <th>Customer payment</th>
                <th>Change</th>
            </tr>
            @foreach ($sales as $sale)
                <tr>
                    <td>{{ $sale->invoice_number }}</td>
                    <td>{{ $sale->sold_at->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $sale->customer?->name ?? 'Walk-in customer' }}</td>
                    <td>{{ $sale->cashier_name }}</td>
                    <td>{{ $sale->items->pluck('product.category.name')->filter()->unique()->join(', ') }}</td>
                    <td>{{ $sale->items->pluck('product.name')->filter()->join(', ') }}</td>
                    <td>
                        {{ $sale->items->map(fn ($item) => ($item->product?->name ?? 'Deleted product').' ('.number_format($item->quantity).' x Rp '.number_format($item->unit_price, 2).')')->join(', ') }}
                    </td>
                    <td>{{ str($sale->payment_method)->headline() }}</td>
                    <td>{{ number_format($sale->subtotal, 2, '.', '') }}</td>
                    <td>{{ number_format($sale->discount, 2, '.', '') }}</td>
                    <td>{{ number_format($sale->total, 2, '.', '') }}</td>
                    <td>{{ number_format($sale->customer_payment, 2, '.', '') }}</td>
                    <td>{{ number_format($sale->change_amount, 2, '.', '') }}</td>
                </tr>
            @endforeach
        </table>
    </body>
</html>
