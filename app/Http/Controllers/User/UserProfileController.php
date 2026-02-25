<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PendingTransaction;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserProfileController extends Controller
{
    public function index($tab = 'account')
    {
        $tabs = ['account', 'security', 'billing', 'notifications', 'connections'];
        if (!in_array($tab, $tabs, true)) {
            $tab = 'account';
        }

        $user = Auth::user();
        $subscription = $user->subscription()->with('plan')->first();
        $billingHistory = PendingTransaction::where('user_id', $user->id)
            ->where('status', 'completed')
            ->latest('created_at')
            ->limit(20)
            ->get();

        return view('content.user.profile.index', compact('tab', 'subscription', 'billingHistory', 'user'));
    }

    public function updateAccount(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
            'country' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:30',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = Auth::user();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->country_code = $data['country'] ?? null;
        $user->phone_number = $data['phone_number'] ?? null;

        if ($request->hasFile('profile_photo')) {
            $user->updateProfilePhoto($request->file('profile_photo'));
        }

        $user->save();

        return redirect()
            ->route('user.profile', ['tab' => 'account'])
            ->with('success', 'Profil mis à jour avec succès.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return redirect()
                ->route('user.profile', ['tab' => 'security'])
                ->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.'])
                ->withInput();
        }

        $user->password = $data['password'];
        $user->save();

        return redirect()
            ->route('user.profile', ['tab' => 'security'])
            ->with('success', 'Mot de passe modifié avec succès.');
    }

    public function deleteAccount(Request $request)
    {
        $data = $request->validate([
            'password' => 'required|string',
            'confirm_delete' => 'accepted',
        ]);

        $user = Auth::user();

        if (!Hash::check($data['password'], $user->password)) {
            return redirect()
                ->route('user.profile', ['tab' => 'security'])
                ->withErrors(['password' => 'Le mot de passe est incorrect.']);
        }

        Auth::logout();
        $user->deleteProfilePhoto();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Votre compte a été supprimé.');
    }

    public function updateNotifications(Request $request)
    {
        $data = $request->validate([
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_chat_id' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $user->telegram_bot_token = $data['telegram_bot_token'] ?? null;
        $user->telegram_chat_id = $data['telegram_chat_id'] ?? null;
        $user->save();

        return redirect()
            ->route('user.profile', ['tab' => 'notifications'])
            ->with('success', 'Paramètres Telegram mis à jour.');
    }
    

    public function testTelegram(Request $request)
    {
        $data = $request->validate([
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_chat_id' => 'nullable|string|max:255',
        ]);
    

        $user = Auth::user();
        $botToken = $data['telegram_bot_token'] ?: $user->telegram_bot_token;
        $chatId = $data['telegram_chat_id'] ?: $user->telegram_chat_id;
    

        if (!$botToken || !$chatId) {
            return redirect()
                ->route('user.profile', ['tab' => 'notifications'])
                ->with('error', 'Veuillez renseigner le bot token et le chat ID avant le test.');
        }
    

        $message = "✅ Test Telegram réussi ! Vous recevrez les notifications de vente ici.";
    

        $sent = app(TelegramService::class)->sendMessage($botToken, $chatId, $message);
    

        return redirect()
            ->route('user.profile', ['tab' => 'notifications'])
            ->with($sent ? 'success' : 'error', $sent ? 'Message de test envoyé.' : 'Échec de l’envoi du message de test.');
    }
}