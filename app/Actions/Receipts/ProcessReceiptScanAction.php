<?php

namespace App\Actions\Receipts;

use App\Models\ReceiptItem;
use App\Models\ReceiptScan;
use App\Models\User;
use App\Services\VisionOcrService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProcessReceiptScanAction
{
    public function __construct(
        protected VisionOcrService $ocrService
    ) {}

    /**
     * Stores the receipt image, calls OCR pipeline, saves items and returns scan model.
     *
     * @param User $user
     * @param UploadedFile|string $file Uploaded file or base64 data url
     * @param string $scanType 'paper_ocr', 'pix_receipt', 'nfce_qrcode'
     * @return ReceiptScan
     */
    public function execute(User $user, UploadedFile|string $file, string $scanType = 'paper_ocr'): ReceiptScan
    {
        // 1. Store image
        if ($file instanceof UploadedFile) {
            $filename = uniqid('receipt_') . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('receipts/' . $user->id, $filename, 'public');
            $fullPath = storage_path('app/public/' . $path);
        } else {
            // Base64 from camera
            $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $file);
            $decoded = base64_decode($imageData);
            $filename = uniqid('receipt_camera_') . '.jpg';
            $path = 'receipts/' . $user->id . '/' . $filename;
            Storage::disk('public')->put($path, $decoded);
            $fullPath = storage_path('app/public/' . $path);
        }

        // 2. OCR extraction
        $extracted = $this->ocrService->extractReceiptData($fullPath, $scanType);

        // Limpeza imediata do arquivo físico: não armazena fotos inúteis no servidor/disco após extrair os dados
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }

        // 3. Create ReceiptScan com todos os dados e sem peso de imagem
        $scan = ReceiptScan::create([
            'user_id' => $user->id,
            'scan_type' => $scanType,
            'image_path' => null,
            'merchant_name' => $extracted['merchant'] ?? null,
            'merchant_tax_id' => $extracted['cnpj'] ?? null,
            'purchased_at' => !empty($extracted['date_time']) ? Carbon::parse($extracted['date_time']) : now(),
            'total_amount' => (float) ($extracted['total_amount'] ?? 0.00),
            'payment_method_detected' => $extracted['payment_method'] ?? 'debit',
            'card_last_digits' => $extracted['card_last_digits'] ?? null,
            'raw_ocr_payload' => $extracted,
            'match_status' => 'pending',
        ]);

        // 4. Create items
        if (!empty($extracted['items']) && is_array($extracted['items'])) {
            foreach ($extracted['items'] as $item) {
                ReceiptItem::create([
                    'receipt_scan_id' => $scan->id,
                    'item_name' => $item['name'] ?? 'Item',
                    'quantity' => (float) ($item['qty'] ?? 1.0),
                    'unit' => $item['unit'] ?? 'UN',
                    'unit_price' => (float) ($item['price'] ?? 0.0),
                    'total_price' => (float) (($item['qty'] ?? 1) * ($item['price'] ?? 0)),
                    'item_category' => $item['category'] ?? 'alimentacao_essencial',
                ]);
            }
        }

        return $scan->load('items');
    }
}
