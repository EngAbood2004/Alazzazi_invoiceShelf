<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Turns the financial summary into short insights that explain *why*
 * numbers moved and what to do next.
 *
 * With OPENAI_API_KEY set, an OpenAI chat model reads a compact JSON digest
 * (no customer contact data) and answers in the interface language. Without
 * a key, or if the call fails, deterministic rules produce the insights, so
 * the dashboard always has something useful to show.
 */
class AiInsightService
{
    public const TONES = ['positive', 'warning', 'negative', 'info'];

    /**
     * @param  array<string, mixed>  $summary  FinancialAnalytics::summary()
     * @return array{source: string, model: string|null, insights: array<int, array{title: string, explanation: string, action: string|null, tone: string}>}
     */
    public function insights(array $summary, ?string $locale = null, bool $refresh = false): array
    {
        $locale ??= app()->getLocale();
        $digest = $this->digest($summary);

        if (filled(config('services.openai.api_key'))) {
            $key = 'ai_insights:'.$locale.':'.md5(json_encode($digest));

            if ($refresh) {
                Cache::forget($key);
            }

            $ai = Cache::remember($key, now()->addHours(6), fn () => $this->askOpenAi($digest, $locale));

            if ($ai !== null && $ai !== []) {
                return ['source' => 'openai', 'model' => (string) config('services.openai.model'), 'insights' => $ai];
            }

            Cache::forget($key);
        }

        return ['source' => 'rules', 'model' => null, 'insights' => $this->rules($summary)];
    }

    /**
     * Compact, anonymised view of the numbers sent to the model.
     *
     * @param  array<string, mixed>  $s
     * @return array<string, mixed>
     */
    public function digest(array $s): array
    {
        return [
            'as_of' => $s['as_of'],
            'currency' => \App\Models\BusinessSetting::get('currency'),
            'month_to_date' => $s['kpis'],
            'last_12_months' => [
                'months' => $s['trend']['months'],
                'revenue' => $s['trend']['revenue'],
                'cash_in' => $s['trend']['cash_in'],
                'expenses' => $s['trend']['expenses'],
            ],
            'forecast_next_3_months' => [
                'method' => $s['forecast']['method'],
                'values' => $s['forecast']['values'],
                'confidence' => $s['forecast']['confidence'],
            ],
            'receivables_aging' => $s['aging'],
            'top_customers_share_pct' => collect($s['top_customers'])->map(fn ($c, $i) => ['customer' => 'C'.($i + 1), 'share' => $c['share'], 'due' => $c['due']])->all(),
            'top_products' => $s['top_products'],
            'expense_categories' => $s['expense_categories'],
            'payment_behaviour' => $s['payment_behaviour'],
            'inventory' => $s['inventory'],
        ];
    }

    /**
     * @param  array<string, mixed>  $digest
     * @return array<int, array{title: string, explanation: string, action: string|null, tone: string}>|null
     */
    private function askOpenAi(array $digest, string $locale): ?array
    {
        $language = $locale === 'ar' ? 'Arabic' : (config("laraventry.locales.{$locale}.name") ?? 'English');

        try {
            $response = Http::baseUrl(rtrim((string) config('services.openai.base_url'), '/'))
                ->withToken((string) config('services.openai.api_key'))
                ->timeout((int) config('services.openai.timeout', 30))
                ->acceptJson()
                ->post('/chat/completions', [
                    'model' => config('services.openai.model'),
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => implode(' ', [
                            'You are a financial analyst for a small business that sells products and invoices customers.',
                            'Read the JSON metrics and write 4 to 6 insights.',
                            'Each insight must explain the likely CAUSE behind a movement using only the numbers given (e.g. which customers, products, expense categories or overdue buckets drive it),',
                            'then give one concrete action. Never invent numbers; quote figures from the data.',
                            "Write in {$language}.",
                            'Reply with JSON: {"insights":[{"title":string,"explanation":string,"action":string,"tone":"positive|warning|negative|info"}]}.',
                        ])],
                        ['role' => 'user', 'content' => json_encode($digest, JSON_UNESCAPED_UNICODE)],
                    ],
                ])
                ->throw();

            $content = json_decode((string) $response->json('choices.0.message.content'), true);

            return collect($content['insights'] ?? [])
                ->filter(fn ($i) => is_array($i) && filled($i['title'] ?? null) && filled($i['explanation'] ?? null))
                ->take(6)
                ->map(fn ($i) => [
                    'title' => mb_substr((string) $i['title'], 0, 120),
                    'explanation' => mb_substr((string) $i['explanation'], 0, 600),
                    'action' => filled($i['action'] ?? null) ? mb_substr((string) $i['action'], 0, 300) : null,
                    'tone' => in_array($i['tone'] ?? null, self::TONES, true) ? $i['tone'] : 'info',
                ])->values()->all();
        } catch (Throwable $e) {
            Log::warning('OpenAI insights failed; using rule-based insights.', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Deterministic insights used without an API key.
     *
     * @param  array<string, mixed>  $s
     * @return array<int, array{title: string, explanation: string, action: string|null, tone: string}>
     */
    public function rules(array $s): array
    {
        $k = $s['kpis'];
        $currency = \App\Models\BusinessSetting::get('currency');
        $money = fn ($v) => number_format((float) $v, 2).' '.$currency;
        $out = [];

        if ($k['revenue']['change'] !== null) {
            $up = $k['revenue']['change'] >= 0;
            $driver = $s['top_products'][0]['name'] ?? null;
            $out[] = [
                'title' => $up ? __('Revenue is up :pct%', ['pct' => $k['revenue']['change']]) : __('Revenue is down :pct%', ['pct' => abs($k['revenue']['change'])]),
                'explanation' => __('Month-to-date revenue is :now against :prev for the same days last month.', ['now' => $money($k['revenue']['value']), 'prev' => $money($k['revenue']['previous'])])
                    .($driver ? ' '.__('The best-selling product over the last 12 months is :name.', ['name' => $driver]) : ''),
                'action' => $up ? __('Keep stock of the best sellers ready.') : __('Follow up open estimates and contact regular customers.'),
                'tone' => $up ? 'positive' : 'warning',
            ];
        }

        if ($k['overdue_count'] > 0) {
            $oldest = collect($s['aging'])->reverse()->first(fn ($b) => $b['count'] > 0 && $b['key'] !== 'current');
            $out[] = [
                'title' => __(':count overdue invoices', ['count' => $k['overdue_count']]),
                'explanation' => __(':amount is overdue. The oldest balances sit in the :bucket bucket.', ['amount' => $money($k['overdue_amount']), 'bucket' => __('aging.'.($oldest['key'] ?? '1_30'))]),
                'action' => __('Send reminders by email and WhatsApp from the invoices page.'),
                'tone' => 'negative',
            ];
        }

        if ($k['collection_rate'] !== null) {
            $good = $k['collection_rate'] >= 85;
            $out[] = [
                'title' => __('Collection rate :pct%', ['pct' => $k['collection_rate']]),
                'explanation' => $good ? __('Customers pay most of what is invoiced.') : __(':amount is still outstanding across open invoices.', ['amount' => $money($k['outstanding'])]),
                'action' => $good ? null : __('Shorten payment terms or offer online payment.'),
                'tone' => $good ? 'positive' : 'warning',
            ];
        }

        $top = $s['top_customers'][0] ?? null;
        if ($top && $top['share'] >= 40) {
            $out[] = [
                'title' => __('High dependency on one customer'),
                'explanation' => __(':name accounts for :pct% of invoiced revenue.', ['name' => $top['name'], 'pct' => $top['share']]),
                'action' => __('Widen the customer base to reduce the risk.'),
                'tone' => 'warning',
            ];
        }

        $forecast = $s['forecast'];
        if (($forecast['values'][0] ?? 0) > 0) {
            $out[] = [
                'title' => __('Next month: about :amount', ['amount' => $money($forecast['values'][0])]),
                'explanation' => $forecast['method'] === 'holt_winters'
                    ? __('Seasonal Holt-Winters model on :months months of history (error :mape%).', ['months' => $forecast['history_months'], 'mape' => $forecast['mape']])
                    : __('Linear trend; the seasonal model starts automatically after 24 months of history.'),
                'action' => null,
                'tone' => 'info',
            ];
        }

        $category = $s['expense_categories'][0] ?? null;
        if ($category) {
            $out[] = [
                'title' => __('Biggest expense: :name', ['name' => $category['name']]),
                'explanation' => __(':name is :pct% of spending over the last 12 months.', ['name' => $category['name'], 'pct' => $category['share']]),
                'action' => null,
                'tone' => 'info',
            ];
        }

        if (($s['inventory']['out_of_stock'] ?? 0) > 0) {
            $out[] = [
                'title' => __(':count products out of stock', ['count' => $s['inventory']['out_of_stock']]),
                'explanation' => __('Out-of-stock products cannot be invoiced from a warehouse.'),
                'action' => __('Record incoming stock or reorder from suppliers.'),
                'tone' => 'warning',
            ];
        }

        if ($out === []) {
            $out[] = ['title' => __('Not enough data yet'), 'explanation' => __('Create invoices and record payments to unlock insights.'), 'action' => null, 'tone' => 'info'];
        }

        return array_slice($out, 0, 6);
    }
}
