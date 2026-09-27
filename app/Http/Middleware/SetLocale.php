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
            $response->setContent(LocalizedText::translateRendered($response->getContent()));
        }

        return $response;
    }
}
