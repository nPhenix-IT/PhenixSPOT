<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Profile::where('user_id', Auth::id())->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    return '
                        <div class="d-inline-block">
                            <a href="javascript:;" class="btn btn-lg btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                <i class="text-primary icon-base ti tabler-dots-vertical"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end m-0">
                                <a href="javascript:;" class="dropdown-item item-edit" data-id="'.$row->id.'"><i class="icon-base ti tabler-edit"></i>  Modifier</a>
                                <div class="dropdown-divider"></div>
                                <a href="javascript:;" class="dropdown-item text-danger item-delete" data-id="'.$row->id.'"><i class="icon-base ti tabler-trash"></i> Supprimer</a>
                            </div>
                        </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        // $user = Auth::user();
        // $hasActiveSubscription = $user->hasRole(['Super-admin', 'Admin']) || ($user->subscription && $user->subscription->isActive());
        return view('content.profiles.index');
    }

    public function store(Request $request)
    {
        $this->validateAndProcessRequest($request);
        return response()->json(['success' => 'Profil créé et synchronisé avec succès.']);
    }

    public function update(Request $request, Profile $profile)
    {
        if ($profile->user_id !== Auth::id()) { abort(403); }
        $this->validateAndProcessRequest($request, $profile);
        return response()->json(['success' => 'Profil mis à jour et synchronisé avec succès.']);
    }

    public function destroy(Profile $profile)
    {
        if ($profile->user_id !== Auth::id()) { return response()->json(['error' => 'Non autorisé'], 403); }
        DB::transaction(function () use ($profile) {
            DB::table('radgroupreply')->where('groupname', $profile->name)->delete();
            $profile->delete();
        });
        return response()->json(['success' => 'Profil supprimé avec succès.']);
    }

    private function validateAndProcessRequest(Request $request, Profile $profile = null)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'limit_type' => 'required|in:both,time,data,unlimited',
            'rate_limit' => 'nullable|string|max:50',
            'device_limit' => 'required|integer|min:1',
            'session_duration' => 'required_if:limit_type,both,time|nullable|integer|min:1',
            'session_unit' => 'required_if:limit_type,both,time|nullable|in:hours,days,weeks,months',
            'data_limit_value' => 'required_if:limit_type,both,data|nullable|integer|min:1',
            'data_unit' => 'required_if:limit_type,both,data|nullable|in:mb,gb',
            'validity_duration' => 'required|integer|min:1',
            'validity_unit' => 'required|in:hours,days,weeks,months',
        ]);

        DB::transaction(function () use ($request, $validated, $profile) {
            $data = $validated;
            $data['session_timeout'] = 0;
            if ($request->limit_type === 'both' || $request->limit_type === 'time') {
                $data['session_timeout'] = $this->convertToSeconds($request->session_duration, $request->session_unit);
            }
            $data['data_limit'] = 0;
            if ($request->limit_type === 'both' || $request->limit_type === 'data') {
                $data['data_limit'] = $this->convertToBytes($request->data_limit_value, $request->data_unit);
            }
            $data['validity_period'] = $this->convertToSeconds($request->validity_duration, $request->validity_unit);

            unset($data['session_duration'], $data['session_unit'], $data['data_limit_value'], $data['data_unit'], $data['validity_duration'], $data['validity_unit']);

            if ($profile) {
                $oldGroupName = $profile->name;
                $profile->update($data);
                $this->syncRadgroupreply($profile, $oldGroupName);
            } else {
                $newProfile = Auth::user()->profiles()->create($data);
                $this->syncRadgroupreply($newProfile);
            }
        });
    }

    private function syncRadgroupreply(Profile $profile, $oldGroupName = null)
    {
        $groupName = $profile->name;
        DB::table('radgroupreply')->where('groupname', $oldGroupName ?? $groupName)->delete();

        $attributes = [['attribute' => 'Simultaneous-Use', 'op' => ':=', 'value' => $profile->device_limit]];
        if ($profile->session_timeout > 0) $attributes[] = ['attribute' => 'Session-Timeout', 'op' => ':=', 'value' => $profile->session_timeout];
        if ($profile->data_limit > 0) $attributes[] = ['attribute' => 'Mikrotik-Total-Limit', 'op' => ':=', 'value' => $profile->data_limit];
        if (!empty($profile->rate_limit)) $attributes[] = ['attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $profile->rate_limit];

        foreach ($attributes as $attr) {
            DB::table('radgroupreply')->insert(['groupname' => $groupName, 'attribute' => $attr['attribute'], 'op' => $attr['op'], 'value' => $attr['value']]);
        }
    }

    private function convertToSeconds($duration, $unit) {
        if (!$duration || !$unit) return 0;
        switch ($unit) {
            case 'hours': return $duration * 3600;
            case 'days': return $duration * 86400;
            case 'weeks': return $duration * 604800;
            case 'months': return $duration * 2592000;
            default: return 0;
        }
    }

    private function convertToBytes($limit, $unit) {
        if (!$limit || !$unit) return 0;
        return $unit === 'mb' ? $limit * 1024 * 1024 : $limit * 1024 * 1024 * 1024;
    }

    public function edit(Profile $profile)
    {
        if ($profile->user_id !== Auth::id()) { return response()->json(['error' => 'Non autorisé'], 403); }
        return response()->json($profile);
    }
}