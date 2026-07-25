<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MikrotikRouter;
use App\Services\Mikrotik\MikrotikService;
use Illuminate\Http\Request;
use Throwable;

class MikrotikRouterController extends Controller
{
    public function __construct(private MikrotikService $mikrotikService)
    {
    }

    public function index()
    {
        $routers = MikrotikRouter::withCount('customers')->orderBy('name')->paginate(15);

        return view('admin.mikrotik-routers.index', compact('routers'));
    }

    public function create()
    {
        return view('admin.mikrotik-routers.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $router = MikrotikRouter::create($data);

        return redirect()->route('mikrotik-routers.index')->with('status', "Router berhasil ditambahkan. {$this->testAndReport($router)}");
    }

    public function edit(MikrotikRouter $mikrotikRouter)
    {
        return view('admin.mikrotik-routers.edit', ['router' => $mikrotikRouter]);
    }

    public function update(Request $request, MikrotikRouter $mikrotikRouter)
    {
        $data = $this->validated($request, $mikrotikRouter);

        $mikrotikRouter->update($data);

        return redirect()->route('mikrotik-routers.index')->with('status', "Router berhasil diperbarui. {$this->testAndReport($mikrotikRouter)}");
    }

    public function pppoe(MikrotikRouter $mikrotikRouter)
    {
        return view('admin.mikrotik-routers.pppoe', ['router' => $mikrotikRouter]);
    }

    public function pppoeData(MikrotikRouter $mikrotikRouter)
    {
        try {
            $sessions = $this->mikrotikService->getActivePppoeSessions($mikrotikRouter);

            return response()->json([
                'online' => true,
                'count' => count($sessions),
                'sessions' => $sessions,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'online' => false,
                'count' => 0,
                'sessions' => [],
                'message' => 'Gagal terhubung ke router. Periksa koneksi dan kredensial API.',
            ]);
        }
    }

    public function routing(MikrotikRouter $mikrotikRouter)
    {
        return view('admin.mikrotik-routers.routing', ['router' => $mikrotikRouter]);
    }

    public function routingData(MikrotikRouter $mikrotikRouter)
    {
        try {
            $routes = $this->mikrotikService->getRoutes($mikrotikRouter);

            return response()->json([
                'online' => true,
                'count' => count($routes),
                'routes' => $routes,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'online' => false,
                'count' => 0,
                'routes' => [],
                'message' => 'Gagal terhubung ke router. Periksa koneksi dan kredensial API.',
            ]);
        }
    }

    public function interfaces(MikrotikRouter $mikrotikRouter)
    {
        return view('admin.mikrotik-routers.interfaces', ['router' => $mikrotikRouter]);
    }

    public function interfacesData(MikrotikRouter $mikrotikRouter)
    {
        try {
            $interfaces = $this->mikrotikService->getInterfaces($mikrotikRouter);

            return response()->json([
                'online' => true,
                'count' => count($interfaces),
                'interfaces' => $interfaces,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'online' => false,
                'count' => 0,
                'interfaces' => [],
                'message' => 'Gagal terhubung ke router. Periksa koneksi dan kredensial API.',
            ]);
        }
    }

    public function destroy(MikrotikRouter $mikrotikRouter)
    {
        if ($mikrotikRouter->customers()->exists()) {
            return back()->with('error', 'Router tidak bisa dihapus karena masih digunakan pelanggan.');
        }

        $mikrotikRouter->delete();

        return redirect()->route('mikrotik-routers.index')->with('status', 'Router berhasil dihapus.');
    }

    private function testAndReport(MikrotikRouter $router): string
    {
        return $this->mikrotikService->testConnection($router)
            ? 'Koneksi berhasil diuji.'
            : 'Peringatan: koneksi ke router gagal, periksa kembali kredensial.';
    }

    private function validated(Request $request, ?MikrotikRouter $router = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'password' => [$router ? 'nullable' : 'required', 'string'],
            'use_ssl' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $data['use_ssl'] = $request->boolean('use_ssl');
        $data['is_active'] = $request->boolean('is_active');

        if ($router && blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }
}
