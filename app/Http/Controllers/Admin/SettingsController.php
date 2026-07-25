<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Notifications\TemplateRenderer;
use App\Services\Payment\TripayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SettingsController extends Controller
{
    public function smtp()
    {
        return view('admin.settings.smtp');
    }

    public function updateSmtp(Request $request)
    {
        $data = $request->validate([
            'mail_mailer' => ['required', 'in:log,smtp'],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string'],
            'mail_encryption' => ['nullable', 'in:none,tls,ssl'],
            'mail_from_address' => ['required', 'email'],
            'mail_from_name' => ['required', 'string', 'max:255'],
        ]);

        foreach ($data as $key => $value) {
            if ($key === 'mail_password' && blank($value)) {
                continue;
            }
            Setting::set($key, $value);
        }

        return redirect()->route('settings.smtp')->with('status', 'Pengaturan SMTP berhasil disimpan.');
    }

    public function testSmtp(Request $request)
    {
        $data = $request->validate(['test_email' => ['required', 'email']]);

        try {
            Mail::raw('Ini adalah email tes dari pengaturan SMTP '.config('app.name').'.', function ($message) use ($data) {
                $message->to($data['test_email'])->subject('Tes SMTP - '.config('app.name'));
            });
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal mengirim email tes: '.$e->getMessage());
        }

        return back()->with('status', 'Email tes berhasil dikirim ke '.$data['test_email'].'.');
    }

    public function tripay()
    {
        return view('admin.settings.tripay');
    }

    public function updateTripay(Request $request)
    {
        $data = $request->validate([
            'tripay_mode' => ['required', 'in:sandbox,production'],
            'tripay_merchant_code' => ['nullable', 'string', 'max:255'],
            'tripay_api_key' => ['nullable', 'string', 'max:255'],
            'tripay_private_key' => ['nullable', 'string', 'max:255'],
            'tripay_default_method' => ['required', 'string', 'max:50'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->route('settings.tripay')->with('status', 'Pengaturan Tripay berhasil disimpan.');
    }

    public function testTripay(TripayService $tripayService)
    {
        try {
            $channels = $tripayService->listPaymentChannels();
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal terhubung ke Tripay: '.$e->getMessage());
        }

        if (empty($channels)) {
            return back()->with('error', 'Koneksi ke Tripay gagal atau kredensial salah. Periksa kembali Merchant Code/API Key/Private Key.');
        }

        $names = collect($channels)->pluck('name')->filter()->implode(', ');

        return back()->with('status', "Koneksi Tripay berhasil. Channel aktif: {$names}");
    }

    public function billing()
    {
        return view('admin.settings.billing');
    }

    public function updateBilling(Request $request)
    {
        $data = $request->validate([
            'invoice_generate_days_before' => ['required', 'integer', 'min:0', 'max:60'],
            'isolation_grace_days' => ['required', 'integer', 'min:0', 'max:60'],
            'reminder_offset_email_h-3' => ['required', 'integer'],
            'reminder_offset_email_h0' => ['required', 'integer'],
            'reminder_offset_email_h+3' => ['required', 'integer'],
            'reminder_offset_email_h+7' => ['required', 'integer'],
            'reminder_offset_whatsapp_h0' => ['required', 'integer'],
            'reminder_offset_whatsapp_h+3' => ['required', 'integer'],
            'reminder_offset_whatsapp_h+7' => ['required', 'integer'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->route('settings.billing')->with('status', 'Pengaturan tagihan berhasil disimpan.');
    }

    public function templates()
    {
        $keys = TemplateRenderer::keys();
        $templates = [];
        foreach ($keys as $key) {
            $defaults = TemplateRenderer::defaultTemplate($key);
            $templates[$key] = [
                'subject' => Setting::get("template_{$key}_subject", $defaults['subject'] ?? null),
                'body' => Setting::get("template_{$key}_body", $defaults['body']),
            ];
        }

        return view('admin.settings.templates', compact('templates'));
    }

    public function updateTemplates(Request $request)
    {
        foreach (TemplateRenderer::keys() as $key) {
            $subject = $request->input("template_{$key}_subject");
            $body = $request->input("template_{$key}_body");

            if ($subject !== null) {
                Setting::set("template_{$key}_subject", $subject);
            }
            if ($body !== null) {
                Setting::set("template_{$key}_body", $body);
            }
        }

        return redirect()->route('settings.templates')->with('status', 'Template notifikasi berhasil disimpan.');
    }

    public function general()
    {
        return view('admin.settings.general');
    }

    public function updateGeneral(Request $request)
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'invoice_number_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'customer_code_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'ticket_number_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'theme_mode' => ['required', 'in:light,dark'],
            'app_locale' => ['required', 'in:id,en'],
        ]);

        Setting::set('app_name', $data['app_name']);
        Setting::set('invoice_number_prefix', strtoupper($data['invoice_number_prefix']));
        Setting::set('customer_code_prefix', strtoupper($data['customer_code_prefix']));
        Setting::set('ticket_number_prefix', strtoupper($data['ticket_number_prefix']));
        Setting::set('theme_mode', $data['theme_mode']);
        Setting::set('app_locale', $data['app_locale']);

        if ($request->hasFile('logo')) {
            $this->deleteLogoFile();
            Setting::set('app_logo_path', $request->file('logo')->store('branding', 'public'));
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteLogoFile();
            Setting::forget('app_logo_path');
        }

        return redirect()->route('settings.general')->with('status', 'Pengaturan aplikasi berhasil disimpan.');
    }

    private function deleteLogoFile(): void
    {
        $path = Setting::get('app_logo_path');
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
