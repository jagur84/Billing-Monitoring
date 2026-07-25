<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class WhatsAppSettingsController extends Controller
{
    public function show(WhatsAppService $whatsAppService)
    {
        $status = $whatsAppService->status();

        return view('admin.settings.whatsapp', compact('status'));
    }

    public function status(WhatsAppService $whatsAppService)
    {
        return response()->json($whatsAppService->status());
    }

    /**
     * Proxy the WhatsApp engine's /qr page through Laravel so the settings page works
     * over the same public domain without exposing the engine's port directly.
     */
    public function qr(): Response
    {
        $response = Http::timeout(10)->get(rtrim(config('whatsapp.engine_url'), '/').'/qr');

        return response($response->body(), $response->status())
            ->header('Content-Type', 'text/html');
    }
}
