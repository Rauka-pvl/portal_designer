<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Support\PublicFileStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    /**
     * Порядок и заголовки колонок шаблона Excel.
     *
     * @var array<string, string>
     */
    private const COLUMNS = [
        'name' => 'Название',
        'sku' => 'Артикул',
        'category' => 'Категория',
        'price' => 'Цена',
        'unit' => 'Единица',
        'description' => 'Описание',
    ];

    public function index(Request $request): View
    {
        $supplier = $this->supplierForUser($request);

        $products = SupplierProduct::query()
            ->where('supplier_id', $supplier->id)
            ->orderByDesc('id')
            ->get();

        return view('supplier.products.index', [
            'products' => $products,
        ]);
    }

    public function show(Request $request, int $productId): View
    {
        $supplier = $this->supplierForUser($request);
        $product = $this->productForSupplier($supplier, $productId);

        return view('supplier.products.show', [
            'product' => $product,
            'supplier' => $supplier,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $supplier = $this->supplierForUser($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $existingKeys = $this->existingDedupeKeys($supplier->id);
        $key = SupplierProduct::dedupeKey($data['name'] ?? null, $data['sku'] ?? null);
        if (in_array($key, $existingKeys, true)) {
            return response()->json([
                'ok' => false,
                'message' => __('products.duplicate'),
            ], 422);
        }

        $product = new SupplierProduct();
        $product->supplier_id = $supplier->id;
        $product->name = $data['name'];
        $product->sku = $data['sku'] ?? null;
        $product->category = $data['category'] ?? null;
        $product->price = $data['price'] ?? null;
        $product->unit = $data['unit'] ?? null;
        $product->description = $data['description'] ?? null;

        if ($request->hasFile('image')) {
            $product->image_path = PublicFileStorage::store($request->file('image'), 'supplier-products');
        }

        $product->save();

        return response()->json([
            'ok' => true,
            'product' => $product,
        ]);
    }

    public function update(Request $request, int $productId): JsonResponse
    {
        $supplier = $this->supplierForUser($request);
        $product = $this->productForSupplier($supplier, $productId);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:50'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        foreach ($data as $field => $value) {
            if (is_string($value)) {
                $value = trim($value);
                if ($value === '' && $field !== 'name') {
                    $value = null;
                }
            }
            $product->{$field} = $value;
        }

        $product->save();

        return response()->json([
            'ok' => true,
            'product' => $product,
        ]);
    }

    public function updateImage(Request $request, int $productId): JsonResponse
    {
        $supplier = $this->supplierForUser($request);
        $product = $this->productForSupplier($supplier, $productId);

        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        if (! empty($product->image_path)) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->image_path = PublicFileStorage::store($request->file('image'), 'supplier-products');
        $product->save();

        return response()->json([
            'ok' => true,
            'image_url' => $product->image_url,
        ]);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        $supplier = $this->supplierForUser($request);
        $product = $this->productForSupplier($supplier, $productId);

        if (! empty($product->image_path)) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return response()->json(['ok' => true]);
    }

    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('products');

        $col = 'A';
        foreach (self::COLUMNS as $header) {
            $sheet->setCellValue($col.'1', $header);
            $sheet->getColumnDimension($col)->setWidth(24);
            $col++;
        }

        $headerRange = 'A1:'.chr(ord('A') + count(self::COLUMNS) - 1).'1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F59E0B');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->fromArray([
            ['Ламинат Дуб', 'LAM-001', 'Напольные покрытия', '5200', 'м²', 'Влагостойкий ламинат 33 класс'],
        ], null, 'A2');

        $writer = new Xlsx($spreadsheet);
        $fileName = 'products_template.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $supplier = $this->supplierForUser($request);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            return back()->with('products_error', __('products.import_error'));
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);

        if (empty($rows)) {
            return back()->with('products_error', __('products.import_empty'));
        }

        // Первая строка — заголовки. Сопоставляем колонки по позиции.
        $map = array_keys(self::COLUMNS);
        array_shift($rows);

        $existingKeys = $this->existingDedupeKeys($supplier->id);
        $seenInFile = [];
        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $values = [];
            foreach ($map as $index => $field) {
                $values[$field] = isset($row[$index]) ? trim((string) $row[$index]) : '';
            }

            if (($values['name'] ?? '') === '') {
                continue;
            }

            $key = SupplierProduct::dedupeKey($values['name'] ?? null, $values['sku'] ?? null);
            if (in_array($key, $existingKeys, true) || isset($seenInFile[$key])) {
                $skipped++;
                continue;
            }

            $price = $values['price'] ?? '';
            $price = $price === '' ? null : (float) str_replace([' ', ','], ['', '.'], $price);

            SupplierProduct::create([
                'supplier_id' => $supplier->id,
                'name' => $values['name'],
                'sku' => ($values['sku'] ?? '') !== '' ? $values['sku'] : null,
                'category' => ($values['category'] ?? '') !== '' ? $values['category'] : null,
                'price' => $price,
                'unit' => ($values['unit'] ?? '') !== '' ? $values['unit'] : null,
                'description' => ($values['description'] ?? '') !== '' ? $values['description'] : null,
            ]);

            $seenInFile[$key] = true;
            $created++;
        }

        return back()->with('products_status', __('products.import_result', [
            'created' => $created,
            'skipped' => $skipped,
        ]));
    }

    /**
     * @return list<string>
     */
    private function existingDedupeKeys(int $supplierId): array
    {
        return SupplierProduct::query()
            ->where('supplier_id', $supplierId)
            ->get(['name', 'sku'])
            ->map(fn (SupplierProduct $p) => SupplierProduct::dedupeKey($p->name, $p->sku))
            ->all();
    }

    private function supplierForUser(Request $request): Supplier
    {
        $supplier = Supplier::query()->where('user_id', (int) $request->user()->id)->first();
        if (! $supplier) {
            abort(404);
        }

        return $supplier;
    }

    private function productForSupplier(Supplier $supplier, int $productId): SupplierProduct
    {
        return SupplierProduct::query()
            ->where('supplier_id', $supplier->id)
            ->where('id', $productId)
            ->firstOrFail();
    }
}
