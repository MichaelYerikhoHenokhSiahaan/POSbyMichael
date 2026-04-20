<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Invoice Gudang {{ $sale->invoice_number }}</title>
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
                padding: 24px;
            }

            .invoice {
                width: 100%;
                max-width: 1120px;
                background: #ffffff;
                border-radius: 24px;
                padding: 28px;
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

            .header {
                display: flex;
                justify-content: space-between;
                gap: 24px;
                border-bottom: 1px solid #cbd5e1;
                padding-bottom: 20px;
            }

            .brand h1 {
                margin: 0;
                font-size: 28px;
                letter-spacing: 0.1em;
            }

            .brand p {
                margin: 8px 0 0;
                color: #475569;
                font-size: 14px;
            }

            .invoice-meta {
                text-align: right;
            }

            .invoice-meta h2 {
                margin: 0;
                font-size: 28px;
            }

            .info-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 16px;
                margin-top: 24px;
            }

            .info-card {
                border: 1px solid #e2e8f0;
                border-radius: 20px;
                padding: 16px;
                background: #f8fafc;
            }

            .info-card .label {
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: 0.12em;
                color: #64748b;
            }

            .info-card .value {
                margin-top: 10px;
                font-size: 16px;
                font-weight: 700;
                color: #0f172a;
            }

            .items {
                width: 100%;
                border-collapse: collapse;
                margin-top: 24px;
                font-size: 14px;
            }

            .items th,
            .items td {
                padding: 14px 12px;
                border-bottom: 1px solid #e2e8f0;
                text-align: left;
            }

            .items th {
                background: #f8fafc;
                color: #475569;
            }

            .items td:last-child,
            .items th:last-child {
                text-align: right;
            }

            .product-meta {
                display: block;
                margin-top: 4px;
                color: #64748b;
                font-size: 12px;
            }

            .summary {
                margin-top: 24px;
                display: flex;
                justify-content: flex-end;
            }

            .summary-box {
                width: 100%;
                max-width: 360px;
                border-radius: 20px;
                background: #f8fafc;
                padding: 20px;
            }

            .summary-row {
                display: flex;
                justify-content: space-between;
                gap: 16px;
                font-size: 14px;
                color: #334155;
            }

            .summary-row + .summary-row {
                margin-top: 12px;
            }

            .summary-row.total {
                border-top: 1px solid #cbd5e1;
                padding-top: 12px;
                font-size: 18px;
                font-weight: 700;
                color: #059669;
            }

            .notes {
                margin-top: 24px;
                border-top: 1px solid #e2e8f0;
                padding-top: 18px;
                font-size: 14px;
            }

            .footer {
                margin-top: 24px;
                text-align: center;
                color: #64748b;
                font-size: 12px;
            }

            .signatures {
                margin-top: 36px;
                display: flex;
                justify-content: space-between;
                gap: 48px;
            }

            .print-footer-section {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .signature-box {
                width: 280px;
                text-align: center;
            }

            .signature-label {
                font-size: 14px;
                font-weight: 700;
                color: #0f172a;
            }

            .signature-space {
                height: 88px;
                border-bottom: 1px solid #94a3b8;
                margin-top: 10px;
            }

            @media print {
                body {
                    background: #ffffff;
                }

                .page {
                    padding: 0;
                }

                .invoice {
                    max-width: none;
                    border-radius: 0;
                    box-shadow: none;
                    padding: 8mm 10mm;
                }

                .toolbar {
                    display: none;
                }

                .header {
                    padding-bottom: 12px;
                }

                .info-grid {
                    gap: 10px;
                    margin-top: 16px;
                }

                .info-card {
                    padding: 10px 12px;
                }

                .items {
                    margin-top: 16px;
                    font-size: 12px;
                }

                .items th,
                .items td {
                    padding: 9px 8px;
                }

                .summary {
                    margin-top: 16px;
                }

                .summary-box {
                    max-width: 320px;
                    padding: 14px 16px;
                }

                .summary-row {
                    font-size: 12px;
                }

                .summary-row + .summary-row {
                    margin-top: 8px;
                }

                .summary-row.total {
                    padding-top: 8px;
                    font-size: 16px;
                }

                .notes {
                    margin-top: 16px;
                    padding-top: 12px;
                    font-size: 12px;
                }

                .signatures {
                    margin-top: 18px;
                    gap: 24px;
                }

                .signature-box {
                    width: 220px;
                }

                .signature-label {
                    font-size: 12px;
                }

                .signature-space {
                    height: 48px;
                    margin-top: 8px;
                }

                .footer {
                    margin-top: 12px;
                    font-size: 10px;
                }

                @page {
                    size: A4 landscape;
                    margin: 10mm;
                }
            }
        </style>
    </head>
    <body>
        <div class="page">
            <div class="invoice">
                <div class="toolbar">
                    <button type="button" class="button button-print" onclick="window.print()">Cetak</button>
                    <a href="{{ route('warehouse-invoices.show', $sale) }}" class="button button-back">Kembali</a>
                </div>

                <div class="header">
                    <div class="brand">
                        <h1>Point Of Sale</h1>
                        <p>Invoice Penjualan Gudang</p>
                    </div>
                    <div class="invoice-meta">
                        <h2>{{ $sale->invoice_number }}</h2>
                        <p>{{ $sale->sold_at->format('d M Y H:i') }}</p>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-card">
                        <div class="label">Pelanggan</div>
                        <div class="value">{{ $sale->customer?->name ?? 'Pelanggan umum' }}</div>
                    </div>
                    <div class="info-card">
                        <div class="label">Kasir</div>
                        <div class="value">{{ $sale->cashier_name }}</div>
                    </div>
                    <div class="info-card">
                        <div class="label">Pembayaran</div>
                        <div class="value">{{ str($sale->payment_method)->headline() }}</div>
                    </div>
                    <div class="info-card">
                        <div class="label">Kembalian</div>
                        <div class="value">Rp {{ number_format($sale->change_amount, 2) }}</div>
                    </div>
                </div>

                <table class="items">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Qty</th>
                            <th>Harga satuan</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->items as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->product->name }}</strong>
                                    <span class="product-meta">{{ $item->product->sku }} • {{ $item->product->unit }}</span>
                                </td>
                                <td>{{ $item->quantity }}</td>
                                <td>Rp {{ number_format($item->unit_price, 2) }}</td>
                                <td>Rp {{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="summary">
                    <div class="summary-box">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>Rp {{ number_format($sale->subtotal, 2) }}</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span>Rp {{ number_format($sale->total, 2) }}</span>
                        </div>
                        <div class="summary-row">
                            <span>Pembayaran pelanggan</span>
                            <span>Rp {{ number_format($sale->customer_payment, 2) }}</span>
                        </div>
                        <div class="summary-row">
                            <span>Kembalian</span>
                            <span>Rp {{ number_format($sale->change_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="notes">
                    <strong>Catatan</strong>
                    <div>{{ $sale->notes ?: 'Tidak ada catatan tambahan.' }}</div>
                </div>

                <div class="print-footer-section">
                    <div class="signatures">
                        <div class="signature-box">
                            <div class="signature-label">Yang Menerima</div>
                            <div class="signature-space"></div>
                        </div>
                        <div class="signature-box">
                            <div class="signature-label">Hormat Kami</div>
                            <div class="signature-space"></div>
                        </div>
                    </div>

                    <div class="footer">
                        <div>Simpan invoice ini sebagai bukti transaksi Gudang.</div>
                        <div>Dibuat oleh Point Of Sale</div>
                    </div>
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
