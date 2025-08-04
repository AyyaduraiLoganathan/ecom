<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Merge guest cart and wishlist items
        $this->mergeGuestData();

        return redirect(RouteServiceProvider::HOME);
    }

    /**
     * Merge guest cart and wishlist data with newly registered user
     */
    private function mergeGuestData(): void
    {
        $sessionId = session()->getId();
        $user = Auth::user();

        // Merge cart items
        $guestCartItems = \App\Models\CartItem::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->get();

        foreach ($guestCartItems as $guestItem) {
            $guestItem->update(['user_id' => $user->id]);
        }

        // Merge wishlist items
        $guestWishlistItems = \App\Models\WishlistItem::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->get();

        foreach ($guestWishlistItems as $guestItem) {
            $guestItem->update(['user_id' => $user->id]);
        }
    }
}
