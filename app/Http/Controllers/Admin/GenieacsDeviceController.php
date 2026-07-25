<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\GenieacsDevice;
use App\Services\GenieAcs\GenieAcsService;
use Illuminate\Http\Request;

class GenieacsDeviceController extends Controller
{
    public function __construct(private GenieAcsService $genieAcsService)
    {
    }

    public function index()
    {
        $devices = GenieacsDevice::with('customer')->orderByDesc('id')->paginate(15);

        return view('admin.genieacs-devices.index', compact('devices'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();

        return view('admin.genieacs-devices.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $device = GenieacsDevice::create($data);

        $this->syncFromGenieAcs($device);

        return redirect()->route('genieacs-devices.index')->with('status', 'Perangkat berhasil ditambahkan.');
    }

    public function show(GenieacsDevice $genieacsDevice)
    {
        $liveData = $this->genieAcsService->getDevice($genieacsDevice->device_id);

        return view('admin.genieacs-devices.show', ['device' => $genieacsDevice, 'liveData' => $liveData]);
    }

    public function edit(GenieacsDevice $genieacsDevice)
    {
        $customers = Customer::orderBy('name')->get();

        return view('admin.genieacs-devices.edit', ['device' => $genieacsDevice, 'customers' => $customers]);
    }

    public function update(Request $request, GenieacsDevice $genieacsDevice)
    {
        $data = $this->validated($request);

        $genieacsDevice->update($data);

        return redirect()->route('genieacs-devices.index')->with('status', 'Perangkat berhasil diperbarui.');
    }

    public function destroy(GenieacsDevice $genieacsDevice)
    {
        $genieacsDevice->delete();

        return redirect()->route('genieacs-devices.index')->with('status', 'Perangkat berhasil dihapus.');
    }

    public function reboot(GenieacsDevice $genieacsDevice)
    {
        if ($this->genieAcsService->reboot($genieacsDevice->device_id)) {
            return back()->with('status', 'Perintah reboot berhasil dikirim ke perangkat.');
        }

        return back()->with('error', 'Gagal mengirim perintah reboot. Periksa koneksi ke server GenieACS.');
    }

    private function syncFromGenieAcs(GenieacsDevice $device): void
    {
        $data = $this->genieAcsService->getDevice($device->device_id);

        if (! $data) {
            return;
        }

        $device->update([
            'serial_number' => data_get($data, 'DeviceID.SerialNumber._value') ?? $device->serial_number,
            'product_class' => data_get($data, 'DeviceID.ProductClass._value') ?? $device->product_class,
            'manufacturer' => data_get($data, 'DeviceID.Manufacturer._value') ?? $device->manufacturer,
            'last_inform_at' => data_get($data, '_lastInform') ? now()->parse(data_get($data, '_lastInform')) : $device->last_inform_at,
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'device_id' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'product_class' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
