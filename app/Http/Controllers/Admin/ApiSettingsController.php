<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiIntegration;
use App\Models\ApiKey;
use App\Models\WebhookSetting;
use App\Models\ApiRequestLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiSettingsController extends Controller
{
    public function index(): View
    {
        $integrations = ApiIntegration::with(['apiKeys', 'webhookSettings'])->get();
        $recentLogs   = ApiRequestLog::with(['integration', 'apiKey'])
            ->orderByDesc('requested_at')
            ->limit(50)
            ->get();

        return view('admin.api-settings.index', compact('integrations', 'recentLogs'));
    }

    public function storeIntegration(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'slug'        => ['required', 'string', 'max:50', 'unique:api_integrations,slug', 'regex:/^[a-z0-9\-]+$/'],
            'base_url'    => ['nullable', 'url', 'max:255'],
            'api_version' => ['required', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['boolean'],
        ]);

        ApiIntegration::create($validated);

        return redirect()->route('admin.api-settings.index')->with('success', 'Entegrasyon oluşturuldu.');
    }

    public function updateIntegration(Request $request, ApiIntegration $integration): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'base_url'    => ['nullable', 'url', 'max:255'],
            'api_version' => ['required', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $integration->update($validated);

        return redirect()->route('admin.api-settings.index')->with('success', 'Entegrasyon güncellendi.');
    }

    public function generateKey(Request $request, ApiIntegration $integration): RedirectResponse
    {
        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:100'],
            'permission_level'     => ['required', 'in:read,write,admin'],
            'description'          => ['nullable', 'string', 'max:500'],
            'allowed_ips'          => ['nullable', 'string', 'max:500'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'expires_at'           => ['nullable', 'date', 'after:today'],
        ]);

        ['key' => $key, 'plain_token' => $plain] = ApiKey::generate(
            $integration->id,
            $validated['name'],
            $validated['permission_level'],
            $validated
        );

        return redirect()
            ->route('admin.api-settings.index')
            ->with('success', 'API anahtarı oluşturuldu.')
            ->with('new_plain_token', $plain)
            ->with('new_key_name', $key->name);
    }

    public function revokeKey(ApiKey $apiKey): RedirectResponse
    {
        $apiKey->revoke();

        return redirect()->route('admin.api-settings.index')->with('success', "'{$apiKey->name}' anahtarı iptal edildi.");
    }

    public function toggleKey(ApiKey $apiKey): RedirectResponse
    {
        $apiKey->update(['is_active' => ! $apiKey->is_active]);
        $status = $apiKey->is_active ? 'aktifleştirildi' : 'pasif yapıldı';

        return redirect()->route('admin.api-settings.index')->with('success', "'{$apiKey->name}' anahtarı {$status}.");
    }

    public function storeWebhook(Request $request, ApiIntegration $integration): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'callback_url' => ['required', 'url', 'max:500'],
            'is_active'    => ['boolean'],
            'retry_count'  => ['nullable', 'integer', 'min:0', 'max:10'],
        ]);

        $validated['is_active']  = $request->boolean('is_active');
        $validated['retry_count'] = $validated['retry_count'] ?? 3;

        $integration->webhookSettings()->create($validated);

        return redirect()->route('admin.api-settings.index')->with('success', 'Webhook eklendi.');
    }

    public function toggleWebhook(WebhookSetting $webhook): RedirectResponse
    {
        $webhook->update(['is_active' => ! $webhook->is_active]);
        $status = $webhook->is_active ? 'aktifleştirildi' : 'pasif yapıldı';

        return redirect()->route('admin.api-settings.index')->with('success', "'{$webhook->name}' webhook {$status}.");
    }

    public function testWebhook(WebhookSetting $webhook): JsonResponse
    {
        if (! filter_var($webhook->callback_url, FILTER_VALIDATE_URL)) {
            return response()->json(['success' => false, 'message' => 'Geçersiz URL.'], 422);
        }

        $payload = [
            'event'     => 'test.ping',
            'timestamp' => now()->toIso8601String(),
            'source'    => config('app.name'),
        ];

        $start = microtime(true);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($webhook->callback_url, $payload);

            $duration = round((microtime(true) - $start) * 1000, 2);
            $webhook->update(['last_triggered_at' => now()]);

            if ($response->successful()) {
                $webhook->update(['last_success_at' => now()]);
                return response()->json(['success' => true, 'status_code' => $response->status(), 'duration_ms' => $duration]);
            }

            return response()->json(['success' => false, 'status_code' => $response->status(), 'duration_ms' => $duration]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function logs(Request $request): JsonResponse
    {
        $logs = ApiRequestLog::with(['integration', 'apiKey'])
            ->when($request->filled('integration_id'), fn ($q) => $q->where('api_integration_id', $request->integration_id))
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'success') {
                    $q->whereBetween('status_code', [200, 299]);
                } elseif ($request->status === 'error') {
                    $q->where(fn ($q2) => $q2->whereNull('status_code')->orWhere('status_code', '>=', 400));
                }
            })
            ->orderByDesc('requested_at')
            ->paginate(25);

        return response()->json($logs);
    }
}
