<?php

namespace App\Support;

use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ProductQr
{
    public static function ensureToken(SupplierProduct $product): SupplierProduct
    {
        if (filled($product->qr_token)) {
            return $product;
        }

        $product->qr_token = self::newToken();
        $product->qr_version = max(1, (int) $product->qr_version);
        $product->qr_generated_at = now();
        $product->save();

        Log::info('product_qr.token_created', [
            'product_id' => $product->id,
            'version' => $product->qr_version,
        ]);

        return $product->fresh();
    }

    public static function reissueToken(SupplierProduct $product): SupplierProduct
    {
        $product->qr_token = self::newToken();
        $product->qr_version = max(1, (int) $product->qr_version) + 1;
        $product->qr_generated_at = now();
        $product->save();

        Log::info('product_qr.token_reissued', [
            'product_id' => $product->id,
            'version' => $product->qr_version,
        ]);

        return $product->fresh();
    }

    public static function newToken(): string
    {
        do {
            $token = Str::lower(Str::random(32));
        } while (SupplierProduct::query()->where('qr_token', $token)->exists());

        return $token;
    }

    public static function publicUrl(SupplierProduct $product): string
    {
        $product = self::ensureToken($product);

        return route('product.qr.resolve', ['token' => $product->qr_token]);
    }

    public static function designerCardUrl(SupplierProduct $product): string
    {
        return route('product.qr.select-project', [
            'supplierId' => $product->supplier_id,
            'productId' => $product->id,
        ]);
    }

    /**
     * Payload stored in session after QR scan so CRM can prefill a supply.
     *
     * @return array{id:int,supplier_id:int,name:string,price:float,unit:string,sku:?string,image_url:?string}
     */
    public static function scanSessionPayload(SupplierProduct $product): array
    {
        return [
            'id' => (int) $product->id,
            'supplier_id' => (int) $product->supplier_id,
            'name' => (string) $product->name,
            'price' => $product->price !== null ? (float) $product->price : 0.0,
            'unit' => (string) ($product->unit ?? ''),
            'sku' => $product->sku !== null ? (string) $product->sku : null,
            'image_url' => $product->image_url !== null ? (string) $product->image_url : null,
        ];
    }

    public static function supplierCardUrl(SupplierProduct $product): string
    {
        return route('supplier.products.show', ['productId' => $product->id]);
    }

    /**
     * Whether the product can be opened by the given user (or guest = false).
     */
    public static function canViewProduct(?User $user, SupplierProduct $product): bool
    {
        if (! $user) {
            return false;
        }

        $product->loadMissing('supplier');
        $supplier = $product->supplier;
        if (! $supplier) {
            return false;
        }

        if (($user->role ?? '') === 'supplier') {
            return (int) $supplier->user_id === (int) $user->id;
        }

        if (($user->role ?? '') === 'designer') {
            return self::designerCanSeeSupplier($user, $supplier);
        }

        if (($user->role ?? '') === 'moderator') {
            return true;
        }

        return false;
    }

    public static function designerCanSeeSupplier(User $designer, Supplier $supplier): bool
    {
        $designerUserId = (int) $designer->id;
        $isOwned = (int) ($supplier->created_by_user_id ?? 0) === $designerUserId
            || ((int) ($supplier->created_by_user_id ?? 0) < 1 && (int) ($supplier->user_id ?? 0) === $designerUserId);
        $isPublicApproved = (string) $supplier->profile_status === 'active'
            && (string) $supplier->moderation_status === 'approved';

        return $isOwned || $isPublicApproved;
    }

    public static function isSupplierVisibleForQr(Supplier $supplier): bool
    {
        // Soft business rule: approved active suppliers are "published" for QR scans by designers.
        // Owned drafts are still openable by the owning designer via canViewProduct.
        return (string) $supplier->profile_status === 'active'
            && (string) $supplier->moderation_status === 'approved';
    }

    public static function downloadFileName(SupplierProduct $product, string $ext): string
    {
        $base = Str::slug(Str::limit((string) $product->name, 40, ''), '-');
        if ($base === '') {
            $base = 'product';
        }
        $short = substr((string) $product->qr_token, 0, 8);

        return 'qr-'.$base.'-'.$short.'.'.$ext;
    }

    public static function svg(string $url, int $scale = 10): string
    {
        self::assertQrLibraryInstalled();

        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'eccLevel' => EccLevel::M,
            'scale' => max(4, $scale),
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'svgAddXmlHeader' => true,
            'outputBase64' => false,
        ]);

        return (new QRCode($options))->render($url);
    }

    /**
     * PNG bytes. Requires ext-gd. Throws if unavailable.
     */
    public static function png(string $url, int $scale = 20): string
    {
        self::assertQrLibraryInstalled();

        if (! extension_loaded('gd')) {
            throw new RuntimeException('GD extension is required for PNG QR export');
        }

        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'eccLevel' => EccLevel::M,
            'scale' => max(8, $scale),
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'outputBase64' => false,
        ]);

        return (new QRCode($options))->render($url);
    }

    private static function assertQrLibraryInstalled(): void
    {
        if (! class_exists(QROptions::class) || ! class_exists(QRCode::class)) {
            throw new RuntimeException(
                'QR library missing. Run: composer require chillerlan/php-qrcode && composer dump-autoload'
            );
        }
    }

    public static function pngAvailable(): bool
    {
        return extension_loaded('gd');
    }
}
