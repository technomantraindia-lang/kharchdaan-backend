<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\RegisterController;
use App\Http\Controllers\Frontend\LoginController;
use App\Http\Controllers\Frontend\CashbackController;
use App\Http\Controllers\Frontend\MlmNetworkController;
use App\Http\Controllers\Frontend\MlmPayoutController;

/*
|--------------------------------------------------------------------------
| Public Website Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isSuperAdmin()
            ? redirect()->route('super-admin.dashboard')
            : redirect()->route('sub-admin.dashboard');
    }
    return redirect()->route('login');
})->name('home');

Route::get('/super-admin', function () {
    if (auth()->check()) {
        return auth()->user()->isSuperAdmin()
            ? redirect()->route('super-admin.dashboard')
            : redirect()->route('sub-admin.dashboard');
    }
    return redirect()->route('super-admin.login');
});

Route::get('/sub-admin', function () {
    if (auth()->check()) {
        return auth()->user()->isSuperAdmin()
            ? redirect()->route('super-admin.dashboard')
            : redirect()->route('sub-admin.dashboard');
    }
    return redirect()->route('sub-admin.login');
});

Route::get('/products', [ProductController::class, 'index'])
    ->name('products.index');

Route::get('/products/{slug}', [ProductController::class, 'show'])
    ->name('products.show');

Route::get('/cart', [CartController::class, 'index'])
    ->name('cart.index');

Route::post('/cart/add/{product}', [CartController::class, 'add'])
    ->name('cart.add');

Route::post('/cart/buy-now/{product}', [CartController::class, 'buyNow'])
    ->name('cart.buyNow');

Route::post('/cart/remove/{product}', [CartController::class, 'remove'])
    ->name('cart.remove');

Route::get('/category/{slug}', [CategoryController::class, 'show'])
    ->name('categories.show');

Route::get('/page/{slug}', [PageController::class, 'show'])
    ->name('pages.show');

Route::get('/media/{path}', function (string $path) {
    $relativePath = ltrim($path, '/');
    if (str_contains($relativePath, '..')) {
        abort(404);
    }

    // 1. Check storage/app/public/
    $storagePath = storage_path('app/public/' . $relativePath);
    if (file_exists($storagePath) && is_file($storagePath)) {
        return response()->file($storagePath);
    }

    // 2. Check public/media/
    $publicMediaPath = public_path('media/' . $relativePath);
    if (file_exists($publicMediaPath) && is_file($publicMediaPath)) {
        return response()->file($publicMediaPath);
    }

    // 3. Check public/images/
    $publicImagesPath = public_path('images/' . $relativePath);
    if (file_exists($publicImagesPath) && is_file($publicImagesPath)) {
        return response()->file($publicImagesPath);
    }

    // 4. Check direct public/ path
    $publicPath = public_path($relativePath);
    if (file_exists($publicPath) && is_file($publicPath)) {
        return response()->file($publicPath);
    }

    abort(404);
})->where('path', '.*')->name('media.file');

/*
|--------------------------------------------------------------------------
| Customer Guest Routes
|--------------------------------------------------------------------------
|
| These pages can only be opened when the customer is not logged in.
|
*/

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])
        ->name('register');

    Route::post('/register', [RegisterController::class, 'register'])
        ->name('register.post');

    Route::get('/login', [LoginController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'login'])
        ->name('login.post');
});

/*
|--------------------------------------------------------------------------
| Logged-in Customer Routes
|--------------------------------------------------------------------------
|
| These pages can only be opened after customer login.
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/my-account', function () {
        return view('frontend.account', [
            'user' => auth()->user(),
        ]);
    })->name('account');

    Route::get('/my-account/cashback', [CashbackController::class, 'index'])
        ->name('cashback.index');
    Route::get('/my-account/cashback/{cashback}', [CashbackController::class, 'show'])
        ->name('cashback.show');
    Route::get('/my-account/cashback/{cashback}/payment-proof', [CashbackController::class, 'paymentProof'])
        ->name('cashback.payment-proof');

    Route::get('/my-account/network', [MlmNetworkController::class, 'index'])
        ->name('network.index');
    Route::get('/my-account/network/levels', [MlmNetworkController::class, 'levels'])
        ->name('network.levels');
    Route::get('/my-account/network/children/{member}', [MlmNetworkController::class, 'children'])
        ->name('network.children');
    Route::get('/my-account/mlm-payouts', [MlmPayoutController::class, 'index'])
        ->name('mlm-payouts.index');
    Route::get('/my-account/mlm-payouts/{cycle}/payment-proof', [MlmPayoutController::class, 'paymentProof'])
        ->name('mlm-payouts.payment-proof');

    Route::post('/logout', [LoginController::class, 'logout'])
        ->name('logout');
});

/*
|--------------------------------------------------------------------------
| Super Admin & Sub-Admin Dedicated Portals
|--------------------------------------------------------------------------
*/

// Super Admin Portal Prefix
Route::prefix('super-admin')
    ->name('super-admin.')
    ->group(base_path('routes/admin.php'));

// Sub-Admin Staff Portal Prefix
Route::prefix('sub-admin')
    ->name('sub-admin.')
    ->group(base_path('routes/admin.php'));

// Admin Alias Prefix for universal route generation
Route::prefix('admin')
    ->name('admin.')
    ->group(base_path('routes/admin.php'));

// Universal Portal Login
Route::get('/login', [\App\Http\Controllers\Admin\LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [\App\Http\Controllers\Admin\LoginController::class, 'login'])->name('login.post');
