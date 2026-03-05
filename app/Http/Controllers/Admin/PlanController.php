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
    $payload = $plan->toArray();
    $features = is_array($payload['features'] ?? null) ? $payload['features'] : [];

    if (!array_key_exists('vouchers_connected', $features) && array_key_exists('active_users', $features)) {
      $features['vouchers_connected'] = $features['active_users'];
    }

    $payload['features'] = $features;

    return response()
      ->json($payload)
      ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
      ->header('Pragma', 'no-cache');
  }

  public function update(Request $request, Plan $plan)
  {
    $validated = $this->validatePlan($request, $plan->id);
    $validated['slug'] = Str::slug($validated['name']);

    $plan->name = $validated['name'];
    $plan->slug = $validated['slug'];
    $plan->description = $validated['description'] ?? null;
    $plan->price_monthly = $validated['price_monthly'];
    $plan->price_annually = $validated['price_annually'];
    $plan->features = $validated['features'];
    $plan->is_active = $validated['is_active'];
    $plan->trial_enabled = $validated['trial_enabled'];
    $plan->trial_days = $validated['trial_days'];
    $plan->save();

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

      // Limites (nouvelle stratégie)
      'features.routers' => 'required|string|max:50',
      'features.vpn_accounts' => 'required|string|max:50',

      // ✅ Remplace "active_users" par "vouchers_connected"
      // => correspond au nombre de vouchers/coupons connectés (actifs dans radacct)
      'features.vouchers_connected' => 'required|string|max:50',

      'features.support_level' => 'nullable|string|max:50',
      'trial_days' => 'nullable|integer|in:7,14',
    ]);

    $validated['features']['pppoe'] = $request->has('features.pppoe');
    $validated['features']['sales_page'] = $request->has('features.sales_page');
    $validated['features']['advanced_reports'] = $request->has('features.advanced_reports');

    // inchangé
    $validated['features']['hotspot'] = true;
    $validated['features']['vouchers'] = true;


    unset($validated['features']['active_users']);
    $validated['is_active'] = $request->has('is_active');
    $validated['trial_enabled'] = $request->has('trial_enabled');
    $validated['trial_days'] = $validated['trial_enabled']
      ? (in_array((int) ($validated['trial_days'] ?? 7), [7, 14], true) ? (int) $validated['trial_days'] : 7)
      : null;

    return $validated;
  }
}