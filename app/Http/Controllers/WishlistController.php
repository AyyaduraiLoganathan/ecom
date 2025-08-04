<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Session;

class WishlistController extends Controller
{
    /**
     * Display the wishlist.
     */
    public function index(): View
    {
        $wishlistItems = $this->getWishlistItems();

        return view('frontend.wishlist.index', compact('wishlistItems'));
    }

    /**
     * Add item to wishlist (AJAX).
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($request->product_id);

        try {
            // Check if item already exists in wishlist
            $existingItem = $this->findWishlistItem($product);

            if ($existingItem) {
                return response()->json([
                    'status' => 'info',
                    'message' => 'Product is already in your wishlist.',
                ]);
            }

            // Create new wishlist item
            WishlistItem::create([
                'user_id' => auth()->id(),
                'session_id' => auth()->check() ? null : Session::getId(),
                'product_id' => $product->id,
            ]);

            $wishlistCount = $this->getWishlistCount();

            return response()->json([
                'status' => 'success',
                'message' => 'Product added to wishlist successfully!',
                'data' => [
                    'wishlist_count' => $wishlistCount,
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->formatted_price,
                        'image' => $product->image_url,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add product to wishlist. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove item from wishlist (AJAX).
     */
    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($request->product_id);

        try {
            $wishlistItem = $this->findWishlistItem($product);

            if (!$wishlistItem) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found in wishlist.',
                ], 404);
            }

            $wishlistItem->delete();

            $wishlistCount = $this->getWishlistCount();

            return response()->json([
                'status' => 'success',
                'message' => 'Product removed from wishlist successfully!',
                'data' => [
                    'wishlist_count' => $wishlistCount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove product from wishlist. Please try again.',
            ], 500);
        }
    }

    /**
     * Toggle item in wishlist (AJAX).
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($request->product_id);

        try {
            $wishlistItem = $this->findWishlistItem($product);

            if ($wishlistItem) {
                // Remove from wishlist
                $wishlistItem->delete();
                $action = 'removed';
                $message = 'Product removed from wishlist successfully!';
            } else {
                // Add to wishlist
                WishlistItem::create([
                    'user_id' => auth()->id(),
                    'session_id' => auth()->check() ? null : Session::getId(),
                    'product_id' => $product->id,
                ]);
                $action = 'added';
                $message = 'Product added to wishlist successfully!';
            }

            $wishlistCount = $this->getWishlistCount();

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => [
                    'action' => $action,
                    'wishlist_count' => $wishlistCount,
                    'in_wishlist' => $action === 'added',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update wishlist. Please try again.',
            ], 500);
        }
    }

    /**
     * Move item from wishlist to cart (AJAX).
     */
    public function moveToCart(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);
        $quantity = $request->get('quantity', 1);

        // Check if product is available
        if (!$product->is_available) {
            return response()->json([
                'status' => 'error',
                'message' => 'This product is currently out of stock.',
            ], 422);
        }

        try {
            // Add to cart using CartController logic
            $cartController = new CartController();
            $addToCartRequest = new Request([
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);

            $cartResponse = $cartController->add($addToCartRequest);
            $cartData = json_decode($cartResponse->getContent(), true);

            if ($cartData['status'] === 'success') {
                // Remove from wishlist
                $wishlistItem = $this->findWishlistItem($product);
                if ($wishlistItem) {
                    $wishlistItem->delete();
                }

                $wishlistCount = $this->getWishlistCount();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Product moved to cart successfully!',
                    'data' => [
                        'wishlist_count' => $wishlistCount,
                        'cart_count' => $cartData['data']['cart_count'],
                    ],
                ]);
            } else {
                return $cartResponse;
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to move product to cart. Please try again.',
            ], 500);
        }
    }

    /**
     * Clear entire wishlist (AJAX).
     */
    public function clear(): JsonResponse
    {
        try {
            if (auth()->check()) {
                WishlistItem::where('user_id', auth()->id())->delete();
            } else {
                WishlistItem::where('session_id', Session::getId())->delete();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Wishlist cleared successfully!',
                'data' => [
                    'wishlist_count' => 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to clear wishlist. Please try again.',
            ], 500);
        }
    }

    /**
     * Get wishlist count (AJAX).
     */
    public function getCount(): JsonResponse
    {
        $count = $this->getWishlistCount();

        return response()->json([
            'status' => 'success',
            'data' => ['wishlist_count' => $count],
        ]);
    }

    /**
     * Check if product is in wishlist (AJAX).
     */
    public function checkProduct(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($request->product_id);
        $inWishlist = $this->findWishlistItem($product) !== null;

        return response()->json([
            'status' => 'success',
            'data' => [
                'in_wishlist' => $inWishlist,
            ],
        ]);
    }

    /**
     * Get wishlist items for current user/session.
     */
    private function getWishlistItems()
    {
        if (auth()->check()) {
            return WishlistItem::with('product.category')
                ->where('user_id', auth()->id())
                ->latest()
                ->get();
        } else {
            return WishlistItem::with('product.category')
                ->where('session_id', Session::getId())
                ->latest()
                ->get();
        }
    }

    /**
     * Get wishlist items count.
     */
    private function getWishlistCount(): int
    {
        if (auth()->check()) {
            return WishlistItem::where('user_id', auth()->id())->count();
        } else {
            return WishlistItem::where('session_id', Session::getId())->count();
        }
    }

    /**
     * Find wishlist item for specific product.
     */
    private function findWishlistItem(Product $product): ?WishlistItem
    {
        $query = WishlistItem::where('product_id', $product->id);

        if (auth()->check()) {
            $query->where('user_id', auth()->id());
        } else {
            $query->where('session_id', Session::getId());
        }

        return $query->first();
    }

    /**
     * Merge guest wishlist with user wishlist after login.
     */
    public function mergeGuestWishlist(string $sessionId): void
    {
        if (!auth()->check()) {
            return;
        }

        $guestWishlistItems = WishlistItem::where('session_id', $sessionId)->get();

        foreach ($guestWishlistItems as $guestItem) {
            $existingItem = WishlistItem::where('user_id', auth()->id())
                ->where('product_id', $guestItem->product_id)
                ->first();

            if (!$existingItem) {
                // Transfer to user
                $guestItem->update([
                    'user_id' => auth()->id(),
                    'session_id' => null,
                ]);
            } else {
                // Remove duplicate guest item
                $guestItem->delete();
            }
        }
    }
}
