<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\KycUpgradeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\SupportController;
use Illuminate\Support\Facades\Route;

/* ---------- Public website ---------- */
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/p2p', [PageController::class, 'p2p'])->name('p2p');
Route::get('/activity.json', [PageController::class, 'activityFeed'])->middleware('throttle:60,1')->name('activity.feed');
Route::get('/chat/poll', [ChatController::class, 'poll'])->middleware('throttle:120,1')->name('chat.poll');
Route::post('/chat/send', [ChatController::class, 'send'])->middleware('throttle:40,1')->name('chat.send');
foreach (['services', 'about', 'insights', 'contact', 'legal'] as $p) {
    Route::get('/' . $p, [PageController::class, 'page'])->defaults('view', $p)->name($p);
}

/* ---------- Auth ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login/identify', [AuthController::class, 'identify'])->middleware('throttle:30,1')->name('login.identify');
    Route::get('/login/password', [AuthController::class, 'showPassword'])->name('login.password');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::get('/login/restart', [AuthController::class, 'restart'])->name('login.restart');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');

    // Forgot password: email → 6-digit code → new password
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.forgot');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendCode'])->middleware('throttle:6,1');
    Route::get('/forgot-password/code', [PasswordResetController::class, 'showCode'])->name('password.code');
    Route::post('/forgot-password/code', [PasswordResetController::class, 'verifyCode'])->middleware('throttle:10,1')->name('password.code.verify');
    Route::post('/forgot-password/resend', [PasswordResetController::class, 'resend'])->middleware('throttle:4,1')->name('password.code.resend');
    Route::get('/forgot-password/new', [PasswordResetController::class, 'showNew'])->name('password.new');
    Route::post('/forgot-password/new', [PasswordResetController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/* ---------- Customer area ---------- */
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/verify-email', [EmailVerificationController::class, 'show'])->name('verification.notice');
    Route::post('/verify-email', [EmailVerificationController::class, 'verify'])->middleware('throttle:10,1')->name('verification.verify');
    Route::post('/verify-email/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:4,1')->name('verification.resend');
});

Route::middleware(['auth', 'active', 'email.verified'])->group(function () {
    Route::get('/consent', [ConsentController::class, 'show'])->name('consent.show');
    Route::post('/consent', [ConsentController::class, 'store'])->name('consent.store');
});

Route::middleware(['auth', 'active', 'email.verified', 'consent'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/kyc', [KycController::class, 'show'])->name('kyc.show');
    Route::post('/kyc', [KycController::class, 'store'])->middleware('throttle:5,1')->name('kyc.store');
    Route::get('/kyc/verifying', [KycController::class, 'verifying'])->name('kyc.verifying');
    Route::get('/kyc/video', [KycUpgradeController::class, 'video'])->name('kyc.video');
    Route::post('/kyc/video', [KycUpgradeController::class, 'storeVideo'])->middleware('throttle:6,1')->name('kyc.video.store');
    Route::get('/kyc/edd', [KycUpgradeController::class, 'edd'])->name('kyc.edd');
    Route::post('/kyc/edd', [KycUpgradeController::class, 'storeEdd'])->middleware('throttle:6,1')->name('kyc.edd.store');
    Route::get('/files/upgrade/{upgrade}/{key}', [FileController::class, 'upgrade'])->name('files.upgrade');

    Route::get('/accounts', [BankAccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts', [BankAccountController::class, 'store'])->name('accounts.store');
    Route::delete('/accounts/{account}', [BankAccountController::class, 'destroy'])->name('accounts.destroy');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/payment', [OrderController::class, 'submitPayment'])->middleware('throttle:10,1')->name('orders.payment');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/orders/{order}/chat', [\App\Http\Controllers\OrderChatController::class, 'feed'])->middleware('throttle:90,1')->name('orders.chat');
    Route::post('/orders/{order}/chat', [\App\Http\Controllers\OrderChatController::class, 'send'])->middleware('throttle:20,1')->name('orders.chat.send');
    Route::get('/files/order-chat/{message}', [\App\Http\Controllers\OrderChatController::class, 'file'])->name('orders.chat.file');

    // Anyone logged in can see the trade screen; placing an order needs approved KYC.
    Route::get('/trade', [OrderController::class, 'create'])->name('trade');
    Route::post('/trade', [OrderController::class, 'store'])->middleware(['kyc', 'throttle:10,1'])->name('trade.store');

    Route::get('/account', [AccountController::class, 'index'])->name('account');
    Route::get('/account/limits', [AccountController::class, 'limits'])->name('account.limits');
    Route::get('/account/security', [AccountController::class, 'security'])->name('account.security');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->middleware('throttle:6,1')->name('account.password');

    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::get('/support/new', [SupportController::class, 'create'])->name('support.create');
    Route::post('/support', [SupportController::class, 'store'])->middleware('throttle:6,1')->name('support.store');
    Route::get('/support/{ticket}', [SupportController::class, 'show'])->name('support.show');
    Route::post('/support/{ticket}/reply', [SupportController::class, 'reply'])->middleware('throttle:15,1')->name('support.reply');
    Route::post('/support/{ticket}/close', [SupportController::class, 'close'])->name('support.close');
    Route::get('/files/ticket/{message}', [FileController::class, 'ticket'])->name('files.ticket');

    Route::get('/files/kyc/{kyc}/{doc}', [FileController::class, 'kyc'])->name('files.kyc');
    Route::get('/files/proof/{order}', [FileController::class, 'proof'])->name('files.proof');
    Route::get('/files/order-qr/{order}', [FileController::class, 'orderQr'])->name('files.orderQr');
    Route::get('/files/qr/{method}', [FileController::class, 'qr'])->name('files.qr');
});

/* ---------- Admin panel ---------- */
Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/payments', [Admin\PaymentSetupController::class, 'index'])->name('payments');
    Route::get('/currencies', [Admin\CurrencyController::class, 'index'])->name('currencies.index');
    Route::get('/currencies/suggest/{code}', [Admin\CurrencyController::class, 'suggest'])->middleware('throttle:30,1')->name('currencies.suggest');
    Route::post('/currencies', [Admin\CurrencyController::class, 'store'])->name('currencies.store');
    Route::put('/currencies/{currency}', [Admin\CurrencyController::class, 'update'])->name('currencies.update');
    Route::delete('/currencies/{currency}', [Admin\CurrencyController::class, 'destroy'])->name('currencies.destroy');

    Route::get('/payout-methods', [Admin\PayoutMethodController::class, 'index'])->name('payout-methods.index');
    Route::post('/payout-methods', [Admin\PayoutMethodController::class, 'store'])->name('payout-methods.store');
    Route::put('/payout-methods/{payoutMethod}', [Admin\PayoutMethodController::class, 'update'])->name('payout-methods.update');
    Route::delete('/payout-methods/{payoutMethod}', [Admin\PayoutMethodController::class, 'destroy'])->name('payout-methods.destroy');

    Route::get('/history', [Admin\ActivityController::class, 'index'])->name('history');

    Route::get('/chats', [Admin\ChatController::class, 'index'])->name('chats.index');
    Route::get('/chats/{chat}', [Admin\ChatController::class, 'show'])->name('chats.show');
    Route::get('/chats/{chat}/poll', [Admin\ChatController::class, 'poll'])->name('chats.poll');
    Route::post('/chats/{chat}/reply', [Admin\ChatController::class, 'reply'])->name('chats.reply');
    Route::post('/chats/{chat}/close', [Admin\ChatController::class, 'close'])->name('chats.close');

    Route::get('/profile', [Admin\ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [Admin\ProfileController::class, 'update'])->middleware('throttle:6,1')->name('profile.update');
    Route::put('/profile/password', [Admin\ProfileController::class, 'password'])->middleware('throttle:6,1')->name('profile.password');

    Route::get('/tickets', [Admin\TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [Admin\TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [Admin\TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/status', [Admin\TicketController::class, 'status'])->name('tickets.status');

    Route::get('/kyc', [Admin\KycController::class, 'index'])->name('kyc.index');
    Route::get('/kyc/{kyc}', [Admin\KycController::class, 'show'])->name('kyc.show');
    Route::post('/kyc/{kyc}/approve', [Admin\KycController::class, 'approve'])->name('kyc.approve');
    Route::post('/kyc/{kyc}/reject', [Admin\KycController::class, 'reject'])->name('kyc.reject');
    Route::get('/kyc-level/{upgrade}', [Admin\KycController::class, 'showUpgrade'])->name('kyc.upgrade.show');
    Route::post('/kyc-level/{upgrade}/approve', [Admin\KycController::class, 'approveUpgrade'])->name('kyc.upgrade.approve');
    Route::post('/kyc-level/{upgrade}/reject', [Admin\KycController::class, 'rejectUpgrade'])->name('kyc.upgrade.reject');

    Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/review', [Admin\OrderController::class, 'review'])->name('orders.review');
    Route::post('/orders/{order}/accept', [Admin\OrderController::class, 'accept'])->name('orders.accept');
    Route::post('/orders/{order}/complete', [Admin\OrderController::class, 'complete'])->name('orders.complete');
    Route::post('/orders/{order}/reject', [Admin\OrderController::class, 'reject'])->name('orders.reject');
    Route::get('/orders/{order}/chat', [\App\Http\Controllers\OrderChatController::class, 'feed'])->name('orders.chat');
    Route::post('/orders/{order}/chat', [\App\Http\Controllers\OrderChatController::class, 'send'])->name('orders.chat.send');
    Route::get('/files/order-chat/{message}', [\App\Http\Controllers\OrderChatController::class, 'file'])->name('orders.chat.file');

    Route::post('/methods/{method}/toggle', [Admin\DepositMethodController::class, 'toggle'])->name('methods.toggle');
    Route::resource('methods', Admin\DepositMethodController::class)->except('show');

    Route::get('/networks', [Admin\NetworkController::class, 'index'])->name('networks.index');
    Route::post('/wallets', [Admin\WalletController::class, 'store'])->name('wallets.store');
    Route::put('/wallets/{wallet}', [Admin\WalletController::class, 'update'])->name('wallets.update');
    Route::delete('/wallets/{wallet}', [Admin\WalletController::class, 'destroy'])->name('wallets.destroy');
    Route::post('/networks', [Admin\NetworkController::class, 'store'])->name('networks.store');
    Route::put('/networks/{network}', [Admin\NetworkController::class, 'update'])->name('networks.update');
    Route::delete('/networks/{network}', [Admin\NetworkController::class, 'destroy'])->name('networks.destroy');

    Route::get('/managers', [Admin\ManagerController::class, 'index'])->name('managers.index');
    Route::post('/managers', [Admin\ManagerController::class, 'store'])->name('managers.store');
    Route::post('/managers/{manager}/toggle', [Admin\ManagerController::class, 'toggle'])->name('managers.toggle');
    Route::put('/managers/{manager}/password', [Admin\ManagerController::class, 'password'])->name('managers.password');
    Route::delete('/managers/{manager}', [Admin\ManagerController::class, 'destroy'])->name('managers.destroy');
    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [Admin\UserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/block', [Admin\UserController::class, 'toggleBlock'])->name('users.block');
    Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/restore', [Admin\UserController::class, 'restore'])->name('users.restore');

    Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings');
    Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
});
