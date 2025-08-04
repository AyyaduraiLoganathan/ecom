<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    /**
     * Display the shopping cart.
     */
    public function index(): View
    {
        $cartItems = $this->getCartItems();
        $cartTotal = $this->getCartTotal($cartItems);

        return view('frontend.cart.index', compact('cartItems', 'cartTotal'));
    }

    /**
     * Add item to cart (AJAX).
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'options' => 'nullable|array',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Check if product is available
        if (!$product->is_available) {
            return response()->json([
                'status' => 'error',
                'message' => 'This product is currently out of stock.',
            ], 422);
        }

        // Check stock quantity
        if ($product->manage_stock && $request->quantity > $product->stock_quantity) {
            return response()->json([
                'status' => 'error',
                'message' => "Only {$product->stock_quantity} items available in stock.",
            ], 422);
        }

        try {
            $cartItem = $this->findOrCreateCartItem($product, $request->options);
            
            // Update quantity
            $newQuantity = $cartItem->quantity + $request->quantity;
            
            // Check total quantity against stock
            if ($product->manage_stock && $newQuantity > $product->stock_quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Cannot add more items. Only {$product->stock_quantity} available in stock.",
                ], 422);
            }

            $cartItem->update([
                'quantity' => $newQuantity,
                'price' => $product->current_price, // Update price in case it changed
            ]);

            $cartCount = $this->getCartCount();

            return response()->json([
                'status' => 'success',
                'message' => 'Product added to cart successfully!',
                'data' => [
                    'cart_count' => $cartCount,
                    'item' => [
                        'id' => $cartItem->id,
                        'product_name' => $product->name,
                        'quantity' => $cartItem->quantity,
                        'price' => $cartItem->formatted_price,
                        'total' => $cartItem->formatted_total_price,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add product to cart. Please try again.',
            ], 500);
        }
    }

    /**
     * Update cart item quantity (AJAX).
     */
    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        // Ensure user owns this cart item
        if (!$this->userOwnsCartItem($cartItem)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.',
            ], 403);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $product = $cartItem->product;

        // Check stock quantity
        if ($product->manage_stock && $request->quantity > $product->stock_quantity) {
            return response()->json([
                'status' => 'error',
                'message' => "Only {$product->stock_quantity} items available in stock.",
            ], 422);
        }

        try {
            $cartItem->update([
                'quantity' => $request->quantity,
                'price' => $product->current_price, // Update price in case it changed
            ]);

            $cartItems = $this->getCartItems();
            $cartTotal = $this->getCartTotal($cartItems);
            $cartCount = $this->getCartCount();

            return response()->json([
                'status' => 'success',
                'message' => 'Cart updated successfully!',
                'data' => [
                    'cart_count' => $cartCount,
                    'cart_total' => '$' . number_format($cartTotal, 2),
                    'item' => [
                        'id' => $cartItem->id,
                        'quantity' => $cartItem->quantity,
                        'total' => $cartItem->formatted_total_price,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update cart. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove item from cart (AJAX).
     */
    public function remove(CartItem $cartItem): JsonResponse
    {
        // Ensure user owns this cart item
        if (!$this->userOwnsCartItem($cartItem)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.',
            ], 403);
        }

        try {
            $cartItem->delete();

            $cartItems = $this->getCartItems();
            $cartTotal = $this->getCartTotal($cartItems);
            $cartCount = $this->getCartCount();

            return response()->json([
                'status' => 'success',
                'message' => 'Item removed from cart successfully!',
                'data' => [
                    'cart_count' => $cartCount,
                    'cart_total' => '$' . number_format($cartTotal, 2),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove item from cart. Please try again.',
            ], 500);
        }
    }

    /**
     * Clear entire cart (AJAX).
     */
    public function clear(): JsonResponse
    {
        try {
            if (auth()->check()) {
                CartItem::where('user_id', auth()->id())->delete();
            } else {
                CartItem::where('session_id', Session::getId())->delete();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Cart cleared successfully!',
                'data' => [
                    'cart_count' => 0,
                    'cart_total' => '$0.00',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to clear cart. Please try again.',
            ], 500);
        }
    }

    /**
     * Get cart count (AJAX).
     */
    public function getCount(): JsonResponse
    {
        $count = $this->getCartCount();

        return response()->json([
            'status' => 'success',
            'data' => ['cart_count' => $count],
        ]);
    }

    /**
     * Get cart items for current user/session.
     */
    private function getCartItems()
    {
        if (auth()->check()) {
            return CartItem::with('product')
                ->where('user_id', auth()->id())
                ->get();
        } else {
            return CartItem::with('product')
                ->where('session_id', Session::getId())
                ->get();
        }
    }

    /**
     * Get cart total.
     */
    private function getCartTotal($cartItems): float
    {
        return $cartItems->sum('total_price');
    }

    /**
     * Get cart items count.
     */
    private function getCartCount(): int
    {
        if (auth()->check()) {
            return CartItem::where('user_id', auth()->id())->sum('quantity');
        } else {
            return CartItem::where('session_id', Session::getId())->sum('quantity');
        }
    }

    /**
     * Find existing cart item or create new one.
     */
    private function findOrCreateCartItem(Product $product, ?array $options = null): CartItem
    {
        $query = CartItem::where('product_id', $product->id);

        if (auth()->check()) {
            $query->where('user_id', auth()->id());
        } else {
            $query->where('session_id', Session::getId());
        }

        // Check for matching options
        if ($options) {
            $query->where('product_options', json_encode($options));
        } else {
            $query->whereNull('product_options');
        }

        $cartItem = $query->first();

        if (!$cartItem) {
            $cartItem = CartItem::create([
                'user_id' => auth()->id(),
                'session_id' => auth()->check() ? null : Session::getId(),
                'product_id' => $product->id,
                'quantity' => 0,
                'price' => $product->current_price,
                'product_options' => $options,
            ]);
        }

        return $cartItem;
    }

    /**
     * Check if user owns the cart item.
     */
    private function userOwnsCartItem(CartItem $cartItem): bool
    {
        if (auth()->check()) {
            return $cartItem->user_id === auth()->id();
        } else {
            return $cartItem->session_id === Session::getId();
        }
    }

    /**
     * Merge guest cart with user cart after login.
     */
    public function mergeGuestCart(string $sessionId): void
    {
        if (!auth()->check()) {
            return;
        }

        $guestCartItems = CartItem::where('session_id', $sessionId)->get();

        foreach ($guestCartItems as $guestItem) {
            $existingItem = CartItem::where('user_id', auth()->id())
                ->where('product_id', $guestItem->product_id)
                ->where('product_options', $guestItem->product_options)
                ->first();

            if ($existingItem) {
                // Merge quantities
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $guestItem->quantity,
                ]);
                $guestItem->delete();
            } else {
                // Transfer to user
                $guestItem->update([
                    'user_id' => auth()->id(),
                    'session_id' => null,
                ]);
            }
        }
    }
}
