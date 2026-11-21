<?php

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Services\Analytics\AiInsightService;
use App\Services\Analytics\FinancialAnalytics;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Smart analytics: KPIs, seasonal forecast, aging and AI-explained insights. */
class InsightsController extends Controller
{
    public function __invoke(Request $request, FinancialAnalytics $analytics, AiInsightService $ai): View
    {
        $summary = $analytics->summary();

        return view('invoicing.insights', [
            'summary' => $summary,
            'ai' => $ai->insights($summary, refresh: $request->boolean('refresh')),
            'currency' => BusinessSetting::get('currency'),
        ]);
    }
}
