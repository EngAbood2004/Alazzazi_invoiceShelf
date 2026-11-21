<?php

namespace App\Services\Invoicing;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Renders HTML to PDF with Gotenberg (headless Chromium), which shapes Arabic
 * text correctly. When GOTENBERG_URL is not configured, or the service does
 * not answer, callers fall back to the printable HTML page.
 */
class PdfService
{
    public function enabled(): bool
    {
        return filled(config('services.gotenberg.url'));
    }

    /**
     * @return string|null PDF bytes, or null when Gotenberg is unavailable
     */
    public function fromHtml(string $html, string $paper = 'A4'): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        [$width, $height] = $paper === 'Letter' ? ['8.5', '11'] : ['8.27', '11.7'];

        try {
            $response = Http::timeout((int) config('services.gotenberg.timeout', 30))
                ->attach('files', $html, 'index.html', ['Content-Type' => 'text/html'])
                ->post(rtrim((string) config('services.gotenberg.url'), '/').'/forms/chromium/convert/html', [
                    'paperWidth' => $width,
                    'paperHeight' => $height,
                    'marginTop' => '0.4',
                    'marginBottom' => '0.4',
                    'marginLeft' => '0.4',
                    'marginRight' => '0.4',
                    'printBackground' => 'true',
                    'preferCssPageSize' => 'false',
                ])
                ->throw();

            return $response->body();
        } catch (ConnectionException|RequestException $e) {
            Log::warning('Gotenberg PDF rendering failed; falling back to printable HTML.', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
