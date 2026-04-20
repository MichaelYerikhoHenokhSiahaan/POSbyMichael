<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Inventory Export</title>
    </head>
    <body>
        <table border="1">
            <tr>
                <td colspan="6">Inventory Export</td>
            </tr>
            <tr>
                <td colspan="6">Search: {{ $search ?: 'All inventory' }}</td>
            </tr>
            <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Unit</th>
            </tr>
            @foreach ($products as $product)
                <tr>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category?->name ?? 'Uncategorized' }}</td>
                    <td>{{ number_format($product->price, 2, '.', '') }}</td>
                    <td>{{ number_format($product->stock) }}</td>
                    <td>{{ $product->unit }}</td>
                </tr>
            @endforeach
        </table>
    </body>
</html>
