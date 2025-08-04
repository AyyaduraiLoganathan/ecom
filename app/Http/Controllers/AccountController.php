<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the account dashboard.
     */
    public function dashboard(): View
    {
        $user = auth()->user();
        
        // Get recent orders
        $recentOrders = $user->orders()
            ->with('items')
            ->latest()
            ->limit(5)
            ->get();

        // Get order statistics
        $orderStats = [
            'total_orders' => $user->orders()->count(),
            'pending_orders' => $user->orders()->where('status', 'pending')->count(),
            'completed_orders' => $user->orders()->where('status', 'delivered')->count(),
            'total_spent' => $user->orders()->where('payment_status', 'paid')->sum('total_amount'),
        ];

        return view('frontend.account.dashboard', compact('user', 'recentOrders', 'orderStats'));
    }

    /**
     * Display user profile.
     */
    public function profile(): View
    {
        $user = auth()->user();
        return view('frontend.account.profile', compact('user'));
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'marketing_emails' => 'boolean',
        ]);

        try {
            auth()->user()->update($request->only([
                'name',
                'email',
                'phone',
                'address',
                'city',
                'state',
                'postal_code',
                'country',
                'date_of_birth',
                'gender',
                'marketing_emails',
            ]));

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update profile. Please try again.',
            ], 500);
        }
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (!Hash::check($request->current_password, auth()->user()->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        try {
            auth()->user()->update([
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Password updated successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update password. Please try again.',
            ], 500);
        }
    }

    /**
     * Display user orders.
     */
    public function orders(Request $request): View
    {
        $query = auth()->user()->orders()->with('items.product');

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Search by order number
        if ($request->has('search') && $request->search) {
            $query->where('order_number', 'like', '%' . $request->search . '%');
        }

        $orders = $query->paginate(10);

        return view('frontend.account.orders', compact('orders'));
    }

    /**
     * Display specific order details.
     */
    public function orderDetails(Order $order): View
    {
        // Ensure user owns this order
        if ($order->user_id !== auth()->id()) {
            abort(404);
        }

        $order->load('items.product');

        return view('frontend.account.order-details', compact('order'));
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(Order $order): JsonResponse
    {
        // Ensure user owns this order
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.',
            ], 403);
        }

        if (!$order->canBeCancelled()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This order cannot be cancelled.',
            ], 422);
        }

        try {
            $order->update(['status' => 'cancelled']);

            // Restore product stock
            foreach ($order->items as $item) {
                if ($item->product && $item->product->manage_stock) {
                    $item->product->increment('stock_quantity', $item->quantity);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Order cancelled successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel order. Please try again.',
            ], 500);
        }
    }

    /**
     * Display user reviews.
     */
    public function reviews(): View
    {
        $reviews = auth()->user()->reviews()
            ->with('product')
            ->latest()
            ->paginate(10);

        return view('frontend.account.reviews', compact('reviews'));
    }

    /**
     * Display user addresses.
     */
    public function addresses(): View
    {
        $user = auth()->user();
        return view('frontend.account.addresses', compact('user'));
    }

    /**
     * Display account settings.
     */
    public function settings(): View
    {
        $user = auth()->user();
        return view('frontend.account.settings', compact('user'));
    }

    /**
     * Update notification preferences.
     */
    public function updateNotifications(Request $request): JsonResponse
    {
        $request->validate([
            'marketing_emails' => 'boolean',
            'order_updates' => 'boolean',
            'product_updates' => 'boolean',
        ]);

        try {
            auth()->user()->update([
                'marketing_emails' => $request->get('marketing_emails', false),
                // Add other notification preferences to user model if needed
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Notification preferences updated successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update preferences. Please try again.',
            ], 500);
        }
    }

    /**
     * Delete user account.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required',
        ]);

        if (!Hash::check($request->password, auth()->user()->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password is incorrect.',
            ], 422);
        }

        try {
            $user = auth()->user();
            
            // Check for pending orders
            $pendingOrders = $user->orders()->whereIn('status', ['pending', 'processing'])->count();
            
            if ($pendingOrders > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete account with pending orders. Please contact support.',
                ], 422);
            }

            // Logout user
            auth()->logout();

            // Delete user (this will cascade delete related records)
            $user->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Account deleted successfully.',
                'redirect_url' => route('home'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete account. Please try again.',
            ], 500);
        }
    }

    /**
     * Download order invoice (placeholder).
     */
    public function downloadInvoice(Order $order)
    {
        // Ensure user owns this order
        if ($order->user_id !== auth()->id()) {
            abort(404);
        }

        // This is a placeholder for PDF invoice generation
        // In a real application, you would use a package like dompdf or tcpdf
        
        return response()->json([
            'status' => 'info',
            'message' => 'Invoice download feature coming soon!',
        ]);
    }

    /**
     * Track order (placeholder for tracking integration).
     */
    public function trackOrder(Order $order): JsonResponse
    {
        // Ensure user owns this order
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.',
            ], 403);
        }

        // This is a placeholder for shipping tracking integration
        // In a real application, you would integrate with shipping providers
        
        $trackingInfo = [
            'tracking_number' => $order->tracking_number ?? 'Not available',
            'status' => $order->status,
            'timeline' => $order->timeline,
            'estimated_delivery' => $order->shipped_at ? 
                $order->shipped_at->addDays(3)->format('M j, Y') : 
                'Not available',
        ];

        return response()->json([
            'status' => 'success',
            'data' => $trackingInfo,
        ]);
    }
}
