<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SalePageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalePageController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $settings = $user->salePageSetting ?: new SalePageSetting([
            'commission_payer' => 'seller',
            'commission_percent' => config('fees.sales_commission_percent'),
        ]);

        return view('content.sales-page.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'title' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:400',
            'primary_color' => 'nullable|string|max:20',
            'commission_payer' => 'required|in:seller,client',
        ]);

        $data['commission_percent'] = config('fees.sales_commission_percent');

        $user->salePageSetting()->updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return redirect()
            ->route('user.sales-page.edit')
            ->with('success', 'Page de vente mise à jour.');
    }
}
