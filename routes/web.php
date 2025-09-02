<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\language\LanguageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\User\RouterController;
use App\Http\Controllers\User\VoucherController;
use App\Http\Controllers\User\WalletController;
use App\Http\Controllers\User\PaymentGatewayController;
use App\Http\Controllers\User\VpnAccountController;
use App\Http\Controllers\User\PricingController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\UserProfileController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\VpnServerController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Admin\RadiusServerController;
use App\Http\Controllers\Admin\RadiusTesterController;
use App\Http\Controllers\Public\SaleController;
use App\Http\Controllers\Public\PaymentController;
use App\Http\Controllers\RadiusWebhookController;

// Page d'accueil
Route::get('/', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

// Changement de langue
Route::get('/lang/{locale}', [LanguageController::class, 'swap']);

// Routes Utilisateur
Route::middleware(['auth'])->name('user.')->group(function () {
    Route::resource('routers', RouterController::class);
    Route::resource('vouchers', VoucherController::class);
    Route::resource('profiles', ProfileController::class);
    Route::post('vouchers/toggle-status/{voucher}', [VoucherController::class, 'toggleStatus'])->name('vouchers.toggle-status');
    Route::post('vouchers/print-by-profile', [VoucherController::class, 'printByProfile'])->name('vouchers.print-by-profile');
    Route::post('vouchers/print', [VoucherController::class, 'print'])->name('vouchers.print');
    Route::get('vouchers/template', [VoucherController::class, 'getTemplate'])->name('vouchers.get-template');
    Route::post('vouchers/template', [VoucherController::class, 'saveTemplate'])->name('vouchers.save-template');
    Route::delete('vouchers/bulk-delete', [VoucherController::class, 'bulkDelete'])->name('vouchers.bulk-delete');
    Route::get('wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
    Route::get('payment-gateways', [PaymentGatewayController::class, 'index'])->name('payment-gateways.index');
    Route::post('payment-gateways', [PaymentGatewayController::class, 'store'])->name('payment-gateways.store');
    Route::resource('vpn-accounts', VpnAccountController::class);
    Route::get('plans', [PricingController::class, 'index'])->name('plans.index');
    Route::get('payment/{plan}/{duration}', [PricingController::class, 'payment'])->name('payment');
    Route::post('apply-coupon', [PricingController::class, 'applyCoupon'])->name('apply-coupon');
    Route::get('profile/{tab?}', [UserProfileController::class, 'index'])->name('profile');
    Route::get('routers/{router}/generate-script', [RouterController::class, 'generateScript'])->name('routers.generate-script');
    Route::resource('routers', RouterController::class);
});

// Routes Admin
Route::middleware(['auth', 'role:Super-admin|Admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('plans', PlanController::class);
    Route::resource('coupons', CouponController::class);
    Route::post('vpn-servers/test-connection', [VpnServerController::class, 'testConnection'])->name('vpn-servers.test-connection');
    Route::resource('vpn-servers', VpnServerController::class);
    Route::get('withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::post('withdrawals/{withdrawalRequest}/approve', [WithdrawalController::class, 'approve'])->name('withdrawals.approve');
    Route::post('withdrawals/{withdrawalRequest}/reject', [WithdrawalController::class, 'reject'])->name('withdrawals.reject');
    Route::resource('radius-servers', RadiusServerController::class);
    Route::get('radius-tester', [RadiusTesterController::class, 'index'])->name('radius-tester.index');
    Route::post('radius-tester/test', [RadiusTesterController::class, 'test'])->name('radius-tester.test');
});

// Routes Publiques
Route::prefix('p')->name('public.')->group(function () {
    Route::get('/{slug}', [SaleController::class, 'show'])->name('sale.show');
    Route::post('/{slug}/purchase', [SaleController::class, 'purchase'])->name('sale.purchase');
});

Route::prefix('payment')->name('public.payment.')->group(function () {
    Route::get('/callback', [PaymentController::class, 'callback'])->name('callback');
    Route::post('/webhook', [PaymentController::class, 'webhook'])->name('webhook');
});

// --- Route pour le Webhook FreeRADIUS ---
// Cette route doit être accessible publiquement par le serveur RADIUS.
Route::post('/radius/webhook', [RadiusWebhookController::class, 'handle'])->name('radius.webhook');