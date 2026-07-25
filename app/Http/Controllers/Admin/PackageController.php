<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::withCount('customers')->orderBy('name')->paginate(15);

        return view('admin.packages.index', compact('packages'));
    }

    public function create()
    {
        return view('admin.packages.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Package::create($data);

        return redirect()->route('packages.index')->with('status', 'Paket berhasil ditambahkan.');
    }

    public function show(Package $package)
    {
        return redirect()->route('packages.edit', $package);
    }

    public function edit(Package $package)
    {
        return view('admin.packages.edit', compact('package'));
    }

    public function update(Request $request, Package $package)
    {
        $data = $this->validated($request);

        $package->update($data);

        return redirect()->route('packages.index')->with('status', 'Paket berhasil diperbarui.');
    }

    public function destroy(Package $package)
    {
        if ($package->customers()->exists()) {
            return back()->with('error', 'Paket tidak bisa dihapus karena masih digunakan pelanggan.');
        }

        $package->delete();

        return redirect()->route('packages.index')->with('status', 'Paket berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'speed_mbps' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
