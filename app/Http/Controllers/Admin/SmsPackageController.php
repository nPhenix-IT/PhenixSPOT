<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsPackage;
use App\Models\SmsSetting;
use Illuminate\Http\Request;

class SmsPackageController extends Controller
{
    public function index()
    {
        $packages = SmsPackage::query()->latest()->get();
        $settings = SmsSetting::current();

        return view('content.admin.sms-packages.index', compact('packages', 'settings'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'credits' => 'required|integer|min:1',
            'price_fcfa' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        SmsPackage::create($data);

        return back()->with('success', 'Pack SMS créé.');
    }

    public function update(Request $request, SmsPackage $smsPackage)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'credits' => 'required|integer|min:1',
            'price_fcfa' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $smsPackage->update($data);

        return back()->with('success', 'Pack SMS mis à jour.');
    }

    public function destroy(SmsPackage $smsPackage)
    {
        $smsPackage->delete();

        return back()->with('success', 'Pack SMS supprimé.');
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'unit_cost_fcfa' => 'required|numeric|min:0',
            'default_sender_name' => 'required|string|max:20',
        ]);

        SmsSetting::current()->update($data);

        return back()->with('success', 'Paramètres SMS mis à jour.');
    }
}
