<?php

namespace App\Http\Controllers\Invoicing;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BusinessSettingController extends Controller
{
    public function edit(): View
    {
        return view('invoicing.settings', [
            'settings' => collect(BusinessSetting::DEFAULTS)->keys()->mapWithKeys(fn ($k) => [$k => BusinessSetting::get($k)])->all(),
            'taxes' => Tax::query()->orderBy('name')->get(),
            'integrations' => [
                'Gotenberg (PDF)' => filled(config('services.gotenberg.url')),
                'OpenAI' => filled(config('services.openai.api_key')),
                'WhatsApp Cloud API' => filled(config('services.whatsapp.access_token')) && filled(config('services.whatsapp.phone_number_id')),
                'WhatsApp template' => filled(config('services.whatsapp.reminder_template')),
                'SMTP' => config('mail.default') === 'smtp' && filled(config('mail.mailers.smtp.username')),
                'Lahza' => filled(config('services.lahza.secret_key')),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:60'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_tax_number' => ['nullable', 'string', 'max:60'],
            'currency' => ['required', 'string', 'size:3'],
            'invoice_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9\-\/]+$/'],
            'estimate_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9\-\/]+$/'],
            'invoice_due_days' => ['required', 'integer', 'min:0', 'max:365'],
            'estimate_valid_days' => ['required', 'integer', 'min:1', 'max:365'],
            'invoice_terms' => ['nullable', 'string', 'max:2000'],
            'reminder_days' => ['required', 'string', 'regex:/^\s*\d+(\s*,\s*\d+)*\s*$/'],
            'reminder_channels' => ['nullable', 'array'],
            'reminder_channels.*' => ['in:email,whatsapp'],
        ]);

        $data['currency'] = strtoupper($data['currency']);
        $data['reminder_days'] = collect(explode(',', $data['reminder_days']))->map(fn ($d) => (int) trim($d))->unique()->sort()->implode(',');
        $data['reminder_channels'] = implode(',', $data['reminder_channels'] ?? []);

        BusinessSetting::put($data);

        return back()->with('success', __('Settings saved.'));
    }

    public function storeTax(Request $request): RedirectResponse
    {
        Tax::create($request->validate(['name' => ['required', 'string', 'max:100'], 'rate' => ['required', 'numeric', 'min:0', 'max:100']]) + ['is_active' => true]);

        return back()->with('success', __('Tax saved.'));
    }

    public function toggleTax(Tax $tax): RedirectResponse
    {
        $tax->update(['is_active' => ! $tax->is_active]);

        return back()->with('success', __('Tax updated.'));
    }
}
