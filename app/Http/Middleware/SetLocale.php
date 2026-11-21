<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Support\LocalizedText;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('laraventry.locales', []));
        $locale = session('locale', config('app.locale', 'ar'));

        if (! in_array($locale, $supported, true)) {
            $locale = config('app.fallback_locale', 'ar');
        }

        App::setLocale($locale);
        session(['locale' => $locale]);

        $response = $next($request);

        if ($response->headers->get('Content-Type') && str_contains($response->headers->get('Content-Type'), 'text/html')) {
            // Illuminate\Http\Response::setContent() replaces the "original"
            // value with the rendered string. Preserve the original View so
            // response assertions (assertViewIs) and view composers keep
            // working after translation.
            $original = $response instanceof \Illuminate\Http\Response
                ? $response->original
                : null;

            $response->setContent(
                LocalizedText::translateRendered($response->getContent())
            );

            if ($response instanceof \Illuminate\Http\Response) {
                $response->original = $original;
            }
        }

        return $response;
    }
}
