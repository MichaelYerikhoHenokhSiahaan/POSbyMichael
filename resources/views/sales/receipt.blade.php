<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Receipt {{ $sale->invoice_number }}</title>
        @php($autoPrint = request()->boolean('auto_print'))
        <style>
            :root {
                color-scheme: light;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: Arial, Helvetica, sans-serif;
                background: #e5e7eb;
                color: #0f172a;
            }

            .page {
                min-height: 100vh;
                display: flex;
                justify-content: center;
                padding: 32px 16px;
            }

            .receipt {
                width: 100%;
                max-width: 302px;
                background: #ffffff;
                border-radius: 24px;
                padding: 18px;
                box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);
            }

            .toolbar {
                display: flex;
                justify-content: flex-end;
                gap: 12px;
                margin-bottom: 20px;
            }

            .button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 10px 16px;
                border-radius: 999px;
                text-decoration: none;
                font-size: 14px;
                font-weight: 700;
                border: none;
                cursor: pointer;
            }

            .button-print {
                background: #059669;
                color: #ffffff;
            }

            .button-back {
                background: #e2e8f0;
                color: #0f172a;
            }

            .brand {
                text-align: center;
                border-bottom: 1px dashed #cbd5e1;
                padding-bottom: 16px;
            }

            .brand h1 {
                margin: 0;
                font-size: 24px;
                letter-spacing: 0.2em;
            }

            .brand p {
                margin: 6px 0 0;
                color: #475569;
                font-size: 13px;
            }

            .meta,
            .totals {
                margin-top: 20px;
                display: grid;
                gap: 10px;
            }

            .meta-row,
            .total-row {
                display: flex;
                justify-content: space-between;
                gap: 16px;
                font-size: 14px;
            }

            .meta-row span:first-child,
            .total-row span:first-child {
                color: #475569;
            }

            .items {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                font-size: 14px;
            }

            .items th,
            .items td {
                padding: 10px 0;
                vertical-align: top;
                border-bottom: 1px dashed #cbd5e1;
                text-align: left;
            }

            .items th:last-child,
            .items td:last-child {
                text-align: right;
            }

            .product-name {
                font-weight: 700;
            }

            .product-meta {
                display: block;
                margin-top: 4px;
                color: #64748b;
                font-size: 12px;
            }

            .totals {
                border-top: 1px dashed #cbd5e1;
                padding-top: 16px;
            }

            .total-row.total {
                font-size: 18px;
                font-weight: 700;
                color: #059669;
            }

            .notes {
                margin-top: 20px;
                border-top: 1px dashed #cbd5e1;
                padding-top: 16px;
                font-size: 14px;
            }

            .footer {
                margin-top: 24px;
                text-align: center;
                color: #64748b;
                font-size: 12px;
            }

            @media print {
                body {
                    background: #ffffff;
                }

                .page {
                    padding: 0;
                }

                .receipt {
                    width: 80mm;
                    max-width: 80mm;
                    border-radius: 0;
                    box-shadow: none;
                    padding: 6mm 5mm;
                }

                .toolbar {
                    display: none;
                }

                @page {
                    size: 80mm auto;
                    margin: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="page">
            <div class="receipt">
                <div class="toolbar">
                    <button type="button" class="button button-print" onclick="window.print()">Print</button>
                    <a href="{{ route('sales.show', $sale) }}" class="button button-back">Back</a>
                </div>

                <div class="brand">
                    <h1>Point Of Sale</h1>
                    <p>Transaction Receipt</p>
                    <p>{{ $sale->invoice_number }}</p>
                </div>

                <div class="meta">
                    <div class="meta-row">
                        <span>Date</span>
                        <span>{{ $sale->sold_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="meta-row">
                        <span>Customer</span>
                        <span>{{ $sale->customer?->name ?? 'Walk-in customer' }}</span>
                    </div>
                    <div class="meta-row">
                        <span>Cashier</span>
                        <span>{{ $sale->cashier_name }}</span>
                    </div>
                    <div class="meta-row">
                        <span>Payment</span>
                        <span>{{ str($sale->payment_method)->headline() }}</span>
                    </div>
                </div>

                <table class="items">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->items as $item)
                            <tr>
                                <td>
                                    <span class="product-name">{{ $item->product->name }}</span>
                                    <span class="product-meta">{{ $item->quantity }} x Rp {{ number_format($item->unit_price, 2) }} • {{ $item->product->unit }}</span>
                                </td>
                                <td>Rp {{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="totals">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>Rp {{ number_format($sale->subtotal, 2) }}</span>
                    </div>
                    <div class="total-row">
                        <span>Discount</span>
                        <span>Rp {{ number_format($sale->discount, 2) }}</span>
                    </div>
                    <div class="total-row total">
                        <span>Total</span>
                        <span>Rp {{ number_format($sale->total, 2) }}</span>
                    </div>
                    <div class="total-row">
                        <span>Customer payment</span>
                        <span>Rp {{ number_format($sale->customer_payment, 2) }}</span>
                    </div>
                    <div class="total-row">
                        <span>Change</span>
                        <span>Rp {{ number_format($sale->change_amount, 2) }}</span>
                    </div>
                </div>

                <div class="notes">
                    <strong>Notes</strong>
                    <div>{{ $sale->notes ?: 'Thank you for shopping with us.' }}</div>
                </div>

                <div class="footer">
                    <div>Please keep this receipt for your records.</div>
                    <div>Generated by Point Of Sale</div>
                </div>
            </div>
        </div>
        @if ($autoPrint)
            <script>
                window.addEventListener('load', () => {
                    setTimeout(() => window.print(), 250);
                });

                window.addEventListener('afterprint', () => {
                    if (window.opener) {
                        window.close();
                    }
                });
            </script>
        @endif
    </body>
</html>
