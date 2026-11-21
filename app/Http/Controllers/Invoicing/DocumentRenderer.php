<?php

namespace App\Http\Controllers\Invoicing;

use App\Models\BusinessSetting;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Services\Invoicing\PdfService;
use Symfony\Component\HttpFoundation\Response;

/** Shared "download PDF / printable page" response for invoices and estimates. */
final class DocumentRenderer
{
    public static function html(Invoice|Estimate $document, string $type, bool $forPdf = false): string
    {
        return view('invoicing.documents.print', [
            'document' => $document,
            'type' => $type,
            'company' => collect(BusinessSetting::DEFAULTS)->keys()->mapWithKeys(fn ($k) => [$k => BusinessSetting::get($k)])->all(),
            'forPdf' => $forPdf,
        ])->render();
    }

    public static function respond(Invoice|Estimate $document, string $type, PdfService $pdf): Response
    {
        $bytes = $pdf->fromHtml(self::html($document, $type, true));

        if ($bytes !== null) {
            return response($bytes, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$document->number.'.pdf"',
            ]);
        }

        return response(self::html($document, $type));
    }
}
