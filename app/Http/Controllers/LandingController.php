<?php

namespace App\Http\Controllers;

use App\Models\Plan;

class LandingController extends Controller
{
    public function index()
    {
        $plans = Plan::where('is_active', true)->orderBy('price_monthly')->get();
        $pageConfigs = ['myLayout' => 'front'];

        return view('content.landing.index', compact('plans', 'pageConfigs'));
    }
}
