<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function index($tab = 'account')
    {
        $tabs = ['account', 'security', 'billing', 'notifications', 'connections'];
        if (!in_array($tab, $tabs)) {
            $tab = 'account';
        }

        return view('content.user.profile.index', compact('tab'));
    }
}
