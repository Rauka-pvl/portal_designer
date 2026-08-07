<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\SupplierProduct;
use App\Support\DesignerSubscription;
use App\Support\ProductQr;
use App\Support\WorkspaceAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ProductQrController extends Controller
{
    /**
     * Public QR entry: /q/{token}
     */
    public function resolve(Request $request, string $token)
    {
        $product = SupplierProduct::query()
            ->where('qr_token', $token)
            ->with('supplier')
            ->first();

        if (! $product || ! $product->supplier) {
            return response()->view('products.qr-unavailable', [
                'title' => __('products.qr_inactive_title'),
                'message' => __('products.qr_inactive_text'),
            ], 404);
        }

        $user = $request->user();

        if (! $user) {
            // Preserve QR URL as intended destination after login/register.
            return redirect()
                ->guest(route('login'))
                ->with('status', __('products.qr_login_hint'));
        }

        if (! ProductQr::canViewProduct($user, $product)) {
            return response()->view('products.qr-unavailable', [
                'title' => __('products.qr_unavailable_title'),
                'message' => __('products.qr_unavailable_text'),
            ], 403);
        }

        if (($user->role ?? '') === 'supplier') {
            return redirect()->route('supplier.products.show', ['productId' => $product->id]);
        }

        if (($user->role ?? '') === 'designer' && ! DesignerSubscription::hasAccess($user)) {
            $request->session()->put('url.intended', ProductQr::designerCardUrl($product));

            return redirect()->route('subscription.index');
        }

        // Designer: pick (or create) a project before adding the scanned product to a supply.
        if (($user->role ?? '') === 'designer') {
            return redirect()->route('product.qr.select-project', [
                'supplierId' => $product->supplier_id,
                'productId' => $product->id,
            ]);
        }

        // Moderator → existing designer product card
        return redirect()->route('suppliers.products.show', [
            'supplierId' => $product->supplier_id,
            'productId' => $product->id,
        ]);
    }

    /**
     * After QR scan: ask designer to choose a project (or create one).
     */
    public function selectProject(Request $request, int $supplierId, int $productId): View
    {
        $user = $request->user();
        abort_unless($user && ($user->role ?? '') === 'designer', 403);

        $product = SupplierProduct::query()
            ->where('id', $productId)
            ->where('supplier_id', $supplierId)
            ->with('supplier')
            ->firstOrFail();

        abort_unless(ProductQr::canViewProduct($user, $product), 403);

        $projects = WorkspaceAccess::scopeProjects(Project::query(), $user)
            ->orderByDesc('updated_at')
            ->orderBy('name')
            ->get(['id', 'name', 'status', 'client_id', 'updated_at']);

        return view('designer.products.qr-select-project', [
            'product' => $product,
            'supplier' => $product->supplier,
            'projects' => $projects,
        ]);
    }

    /**
     * Persist scanned product in session and send designer into CRM
     * (existing project → open supplies create, or create project first).
     */
    public function assignToProject(Request $request, int $supplierId, int $productId)
    {
        $user = $request->user();
        abort_unless($user && ($user->role ?? '') === 'designer', 403);

        $product = SupplierProduct::query()
            ->where('id', $productId)
            ->where('supplier_id', $supplierId)
            ->with('supplier')
            ->firstOrFail();

        abort_unless(ProductQr::canViewProduct($user, $product), 403);

        $data = $request->validate([
            'action' => ['required', 'in:select,create'],
            'project_id' => ['nullable', 'integer', 'required_if:action,select'],
        ]);

        $request->session()->put('qr_scan_product', ProductQr::scanSessionPayload($product));

        if ($data['action'] === 'create') {
            return redirect()->route('projects.index', ['create' => 1]);
        }

        $project = WorkspaceAccess::scopeProjects(Project::query(), $user)
            ->whereKey((int) $data['project_id'])
            ->firstOrFail();

        return redirect()->route('projects.index', [
            'open' => $project->id,
            'tab' => 'supplies',
            'create' => 1,
        ]);
    }

    public function modalData(Request $request, int $productId)
    {
        $product = $this->ownedProductOrFail($request, $productId);
        ProductQr::ensureToken($product);

        $url = ProductQr::publicUrl($product);

        return response()->json([
            'ok' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'image_url' => $product->image_url,
            ],
            'url' => $url,
            'version' => (int) $product->qr_version,
            'png_available' => ProductQr::pngAvailable(),
            'preview_svg' => ProductQr::svg($url, 6),
            'download_png' => route('supplier.products.qr.download', ['productId' => $product->id, 'format' => 'png']),
            'download_svg' => route('supplier.products.qr.download', ['productId' => $product->id, 'format' => 'svg']),
            'print_url' => route('supplier.products.qr.print', ['productId' => $product->id]),
            'open_url' => ProductQr::supplierCardUrl($product),
        ]);
    }

    public function download(Request $request, int $productId, string $format)
    {
        $format = strtolower($format);
        if (! in_array($format, ['png', 'svg'], true)) {
            abort(404);
        }

        $product = $this->ownedProductOrFail($request, $productId);
        ProductQr::ensureToken($product);
        $url = ProductQr::publicUrl($product);

        try {
            if ($format === 'svg') {
                $body = ProductQr::svg($url, 12);
                $mime = 'image/svg+xml';
            } else {
                $body = ProductQr::png($url, 20);
                $mime = 'image/png';
            }
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->with('products_error', __('products.qr_download_error'));
        }

        $filename = ProductQr::downloadFileName($product, $format);

        return response($body, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function printCard(Request $request, int $productId): View
    {
        $product = $this->ownedProductOrFail($request, $productId);
        ProductQr::ensureToken($product);
        $url = ProductQr::publicUrl($product);

        return view('supplier.products.qr-print', [
            'product' => $product,
            'qrSvg' => ProductQr::svg($url, 8),
            'qrUrl' => $url,
        ]);
    }

    public function reissue(Request $request, int $productId)
    {
        $product = $this->ownedProductOrFail($request, $productId);
        ProductQr::reissueToken($product);

        return response()->json([
            'ok' => true,
            'message' => __('products.qr_reissued'),
            'url' => ProductQr::publicUrl($product),
            'version' => (int) $product->qr_version,
            'preview_svg' => ProductQr::svg(ProductQr::publicUrl($product), 6),
            'download_png' => route('supplier.products.qr.download', ['productId' => $product->id, 'format' => 'png']),
            'download_svg' => route('supplier.products.qr.download', ['productId' => $product->id, 'format' => 'svg']),
            'print_url' => route('supplier.products.qr.print', ['productId' => $product->id]),
        ]);
    }

    private function ownedProductOrFail(Request $request, int $productId): SupplierProduct
    {
        $user = $request->user();
        abort_unless($user && ($user->role ?? '') === 'supplier', 403);

        $supplier = $user->supplierProfile;
        abort_unless($supplier, 404);

        return SupplierProduct::query()
            ->where('supplier_id', $supplier->id)
            ->where('id', $productId)
            ->firstOrFail();
    }
}
