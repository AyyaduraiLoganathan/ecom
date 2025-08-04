<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Home and public pages
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [HomeController::class, 'searchProducts'])->name('search');
Route::get('/instagram-feed', [HomeController::class, 'getInstagramFeed'])->name('instagram.feed');

// Newsletter routes
Route::post('/newsletter/subscribe', [HomeController::class, 'subscribeNewsletter'])->name('newsletter.subscribe');
Route::get('/newsletter/verify/{token}', [HomeController::class, 'verifyNewsletter'])->name('newsletter.verify');
Route::get('/newsletter/unsubscribe/{email}', [HomeController::class, 'unsubscribeNewsletter'])->name('newsletter.unsubscribe');

// Product routes
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/{product}', [ProductController::class, 'show'])->name('show');
    Route::get('/{product}/quick-view', [ProductController::class, 'quickView'])->name('quick-view');
    Route::get('/category/{category}', [ProductController::class, 'getByCategory'])->name('by-category');
    Route::post('/{product}/reviews', [ProductController::class, 'submitReview'])->name('reviews.store');
    Route::get('/{product}/reviews', [ProductController::class, 'getReviews'])->name('reviews.index');
});

// Shop page (alias for products)
Route::get('/shop', [ProductController::class, 'index'])->name('shop');

// Cart routes
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::put('/{cartItem}', [CartController::class, 'update'])->name('update');
    Route::delete('/{cartItem}', [CartController::class, 'remove'])->name('remove');
    Route::delete('/', [CartController::class, 'clear'])->name('clear');
    Route::get('/count', [CartController::class, 'getCount'])->name('count');
});

// Wishlist routes
Route::prefix('wishlist')->name('wishlist.')->group(function () {
    Route::get('/', [WishlistController::class, 'index'])->name('index');
    Route::post('/add', [WishlistController::class, 'add'])->name('add');
    Route::delete('/remove', [WishlistController::class, 'remove'])->name('remove');
    Route::post('/toggle', [WishlistController::class, 'toggle'])->name('toggle');
    Route::post('/move-to-cart', [WishlistController::class, 'moveToCart'])->name('move-to-cart');
    Route::delete('/', [WishlistController::class, 'clear'])->name('clear');
    Route::get('/count', [WishlistController::class, 'getCount'])->name('count');
    Route::get('/check', [WishlistController::class, 'checkProduct'])->name('check');
});

// Checkout routes (requires authentication)
Route::middleware('auth')->prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');
    Route::post('/process', [CheckoutController::class, 'process'])->name('process');
    Route::get('/success/{order}', [CheckoutController::class, 'success'])->name('success');
    Route::post('/create-payment-intent', [CheckoutController::class, 'createPaymentIntent'])->name('create-payment-intent');
});

// Stripe webhook (outside auth middleware)
Route::post('/stripe/webhook', [CheckoutController::class, 'handleStripeWebhook'])->name('stripe.webhook');

// Account routes (requires authentication)
Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::put('/password', [AccountController::class, 'updatePassword'])->name('password.update');
    
    // Orders
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AccountController::class, 'orderDetails'])->name('orders.show');
    Route::put('/orders/{order}/cancel', [AccountController::class, 'cancelOrder'])->name('orders.cancel');
    Route::get('/orders/{order}/track', [AccountController::class, 'trackOrder'])->name('orders.track');
    Route::get('/orders/{order}/invoice', [AccountController::class, 'downloadInvoice'])->name('orders.invoice');
    
    // Reviews
    Route::get('/reviews', [AccountController::class, 'reviews'])->name('reviews');
    
    // Addresses
    Route::get('/addresses', [AccountController::class, 'addresses'])->name('addresses');
    
    // Settings
    Route::get('/settings', [AccountController::class, 'settings'])->name('settings');
    Route::put('/notifications', [AccountController::class, 'updateNotifications'])->name('notifications.update');
    Route::delete('/delete', [AccountController::class, 'deleteAccount'])->name('delete');
});

// Static pages
Route::view('/faq', 'frontend.pages.faq')->name('faq');
Route::view('/about', 'frontend.pages.about')->name('about');
Route::view('/contact', 'frontend.pages.contact')->name('contact');
Route::view('/privacy', 'frontend.pages.privacy')->name('privacy');
Route::view('/terms', 'frontend.pages.terms')->name('terms');

// Authentication routes (Laravel Breeze will add these)
require __DIR__.'/auth.php';
