<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('products.qr_print_title') }} — {{ $product->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }
        .sheet {
            width: 105mm;
            min-height: 148mm;
            margin: 16px auto;
            padding: 14mm 12mm;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        .brand {
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #94a3b8;
        }
        .name {
            font-size: 18px;
            font-weight: 650;
            text-align: center;
            line-height: 1.25;
        }
        .sku { font-size: 12px; color: #64748b; }
        .img {
            width: 48mm;
            height: 48mm;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .qr {
            width: 55mm;
            height: 55mm;
        }
        .qr svg { width: 100%; height: 100%; }
        .hint {
            font-size: 13px;
            color: #64748b;
            text-align: center;
        }
        .actions {
            max-width: 105mm;
            margin: 0 auto 24px;
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        .actions button {
            min-height: 44px;
            padding: 0 16px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #fff;
            cursor: pointer;
            font-size: 14px;
        }
        .actions .primary {
            background: #f59e0b;
            border-color: #f59e0b;
            color: #fff;
        }
        @media print {
            body { background: #fff; }
            .actions { display: none !important; }
            .sheet {
                margin: 0;
                border: none;
                border-radius: 0;
                width: 100%;
                min-height: 100vh;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" class="primary" onclick="window.print()">{{ __('products.qr_print') }}</button>
        <button type="button" onclick="window.close()">{{ __('products.cancel') }}</button>
    </div>
    <div class="sheet">
        <div class="brand">{{ config('app.name', 'Portal') }}</div>
        <div class="name">{{ $product->name }}</div>
        @if ($product->sku)
            <div class="sku">{{ __('products.f_sku') }}: {{ $product->sku }}</div>
        @endif
        @if ($product->image_url)
            <img class="img" src="{{ $product->image_url }}" alt="">
        @endif
        <div class="qr" aria-label="{{ __('products.qr_aria') }}">{!! $qrSvg !!}</div>
        <div class="hint">{{ __('products.qr_scan_hint') }}</div>
    </div>
</body>
</html>
