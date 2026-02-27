<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Plan::latest();
            return DataTables::of($data)
                ->addColumn('price', fn ($row) => number_format($row->price_monthly, 0, ',', ' ') . ' FCFA / Mois')
                ->addColumn('trial', function ($row) {
                    if (!$row->trial_enabled) {
                        return '<span class="badge bg-label-secondary">Désactivé</span>';
                    }

                    $days = in_array((int) $row->trial_days, [7, 14], true) ? (int) $row->trial_days : 7;
                    return '<span class="badge bg-label-info">Actif - ' . $days . ' jours</span>';
                })
                ->addColumn('status', fn ($row) => $row->is_active ? '<span class="badge bg-label-success">Actif</span>' : '<span class="badge bg-label-secondary">Inactif</span>')
                ->addColumn('action', function ($row) {
                    return '
                        <div class="d-inline-block">
                            <a href="javascript:;" class="btn btn-md text-primary btn-icon item-edit" data-id="' . $row->id . '"><i class="icon-base ti tabler-pencil"></i></a>
                            <a href="javascript:;" class="btn btn-md text-danger btn-icon item-delete" data-id="' . $row->id . '"><i class="icon-base ti tabler-trash"></i></a>
                        </div>';
                })
                ->rawColumns(['trial', 'status', 'action'])
                ->make(true);
        }

        return view('content.admin.plans.index');
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);
        $validated['slug'] = Str::slug($validated['name']);
        Plan::create($validated);

        return response()->json(['success' => 'Forfait créé avec succès.']);
    }

    public function edit(Plan $plan)
    {
        return response()->json($plan);
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $this->validatePlan($request, $plan->id);
        $validated['slug'] = Str::slug($validated['name']);
        $plan->update($validated);

        return response()->json(['success' => 'Forfait mis à jour avec succès.']);
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return response()->json(['success' => 'Forfait supprimé avec succès.']);
    }

    private function validatePlan(Request $request, $planId = null)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('plans')->ignore($planId)],
            'description' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_annually' => 'required|numeric|min:0',
            'features.routers' => 'required|string|max:50',
            'features.vpn_accounts' => 'required|string|max:50',
            'features.active_users' => 'required|string|max:50',
            'features.support_level' => 'nullable|string|max:50',
            'trial_days' => 'nullable|integer|in:7,14',
        ]);

        $validated['features']['pppoe'] = $request->has('features.pppoe');
        $validated['features']['sales_page'] = $request->has('features.sales_page');
        $validated['features']['advanced_reports'] = $request->has('features.advanced_reports');
        $validated['features']['hotspot'] = true;
        $validated['features']['vouchers'] = true;
        $validated['is_active'] = $request->has('is_active');
        $validated['trial_enabled'] = $request->has('trial_enabled');
        $validated['trial_days'] = $validated['trial_enabled']
            ? (in_array((int) ($validated['trial_days'] ?? 7), [7, 14], true) ? (int) $validated['trial_days'] : 7)
            : null;

        return $validated;
    }
}