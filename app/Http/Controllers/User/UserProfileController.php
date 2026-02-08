<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
<<<<<<< HEAD
=======
use App\Services\TelegramService;
>>>>>>> master

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
            ->route('user.profile', 'notifications')
            ->with('success', 'Paramètres Telegram mis à jour.');
    }
<<<<<<< HEAD

=======
    
>>>>>>> master
    public function testTelegram(Request $request)
    {
        $data = $request->validate([
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_chat_id' => 'nullable|string|max:255',
        ]);
<<<<<<< HEAD

        $user = Auth::user();
        $botToken = $data['telegram_bot_token'] ?: $user->telegram_bot_token;
        $chatId = $data['telegram_chat_id'] ?: $user->telegram_chat_id;

=======
    
        $user = Auth::user();
        $botToken = $data['telegram_bot_token'] ?: $user->telegram_bot_token;
        $chatId = $data['telegram_chat_id'] ?: $user->telegram_chat_id;
    
>>>>>>> master
        if (!$botToken || !$chatId) {
            return redirect()
                ->route('user.profile', 'notifications')
                ->with('error', 'Veuillez renseigner le bot token et le chat ID avant le test.');
        }
<<<<<<< HEAD

        $message = "✅ Test Telegram réussi ! Vous recevrez les notifications de vente ici.";

        $sent = app(TelegramService::class)->sendMessage($botToken, $chatId, $message);

=======
    
        $message = "✅ Test Telegram réussi ! Vous recevrez les notifications de vente ici.";
    
        $sent = app(TelegramService::class)->sendMessage($botToken, $chatId, $message);
    
>>>>>>> master
        return redirect()
            ->route('user.profile', 'notifications')
            ->with($sent ? 'success' : 'error', $sent ? 'Message de test envoyé.' : 'Échec de l’envoi du message de test.');
    }
}
