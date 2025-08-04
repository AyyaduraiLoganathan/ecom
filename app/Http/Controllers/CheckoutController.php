<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class CheckoutController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the checkout page.
     */
    public function index(): View
    {
        $cartItems = CartItem::with('product')
            ->where('user_id', auth()->id())
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $subtotal = $cartItems->sum('total_price');
        $taxRate = 0.08; // 8% tax rate
        $taxAmount = $subtotal * $taxRate;
        $shippingAmount = $this->calculateShipping($subtotal);
        $total = $subtotal + $taxAmount + $shippingAmount;

        return view('frontend.checkout.index', compact(
            'cartItems',
            'subtotal',
            'taxAmount',
            'shippingAmount',
            'total'
        ));
    }

    /**
     * Process the checkout.
     */
    public function process(Request $request): JsonResponse
    {
        $request->validate([
            'billing_address' => 'required|array',
            'billing_address.name' => 'required|string|max:255',
            'billing_address.email' => 'required|email|max:255',
            'billing_address.phone' => 'required|string|max:20',
            'billing_address.address' => 'required|string|max:255',
            'billing_address.city' => 'required|string|max:100',
            'billing_address.state' => 'required|string|max:100',
            'billing_address.postal_code' => 'required|string|max:20',
            'billing_address.country' => 'required|string|max:100',
            'shipping_address' => 'required|array',
            'payment_method' => 'required|in:stripe,paypal',
            'payment_token' => 'required|string',
        ]);

        $cartItems = CartItem::with('product')
            ->where('user_id', auth()->id())
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your cart is empty.',
            ], 422);
        }

        // Calculate totals
        $subtotal = $cartItems->sum('total_price');
        $taxRate = 0.08;
        $taxAmount = $subtotal * $taxRate;
        $shippingAmount = $this->calculateShipping($subtotal);
        $total = $subtotal + $taxAmount + $shippingAmount;

        DB::beginTransaction();

        try {
            // Create order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => auth()->id(),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'total_amount' => $total,
                'currency' => 'USD',
                'payment_status' => 'pending',
                'payment_method' => $request->payment_method,
                'billing_address' => $request->billing_address,
                'shipping_address' => $request->shipping_address,
            ]);

            // Create order items
            foreach ($cartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'product_sku' => $cartItem->product->sku,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->price,
                    'total_price' => $cartItem->total_price,
                    'product_options' => $cartItem->product_options,
                ]);

                // Update product stock
                if ($cartItem->product->manage_stock) {
                    $cartItem->product->decrement('stock_quantity', $cartItem->quantity);
                }
            }

            // Process payment
            $paymentResult = $this->processPayment($order, $request->payment_token);

            if ($paymentResult['success']) {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_id' => $paymentResult['payment_id'],
                    'status' => 'processing',
                ]);

                // Clear cart
                CartItem::where('user_id', auth()->id())->delete();

                DB::commit();

                // Send order confirmation email (implement mail sending logic)
                // Mail::to($order->user->email)->send(new OrderConfirmationMail($order));

                return response()->json([
                    'status' => 'success',
                    'message' => 'Order placed successfully!',
                    'data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'redirect_url' => route('checkout.success', $order->id),
                    ],
                ]);
            } else {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment failed: ' . $paymentResult['error'],
                ], 422);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Order processing failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Display order success page.
     */
    public function success(Order $order): View
    {
        // Ensure user owns this order
        if ($order->user_id !== auth()->id()) {
            abort(404);
        }

        $order->load('items.product');

        return view('frontend.checkout.success', compact('order'));
    }

    /**
     * Create Stripe payment intent.
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $cartItems = CartItem::with('product')
            ->where('user_id', auth()->id())
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your cart is empty.',
            ], 422);
        }

        $subtotal = $cartItems->sum('total_price');
        $taxAmount = $subtotal * 0.08;
        $shippingAmount = $this->calculateShipping($subtotal);
        $total = $subtotal + $taxAmount + $shippingAmount;

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $paymentIntent = PaymentIntent::create([
                'amount' => $total * 100, // Amount in cents
                'currency' => 'usd',
                'metadata' => [
                    'user_id' => auth()->id(),
                    'cart_items_count' => $cartItems->count(),
                ],
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'client_secret' => $paymentIntent->client_secret,
                    'amount' => $total,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create payment intent.',
            ], 500);
        }
    }

    /**
     * Handle Stripe webhook.
     */
    public function handleStripeWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    $this->handleSuccessfulPayment($paymentIntent);
                    break;

                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;
                    $this->handleFailedPayment($paymentIntent);
                    break;

                default:
                    // Unhandled event type
                    break;
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error'], 400);
        }
    }

    /**
     * Calculate shipping cost.
     */
    private function calculateShipping(float $subtotal): float
    {
        // Free shipping over $100
        if ($subtotal >= 100) {
            return 0;
        }

        // Standard shipping
        return 9.99;
    }

    /**
     * Process payment based on method.
     */
    private function processPayment(Order $order, string $paymentToken): array
    {
        switch ($order->payment_method) {
            case 'stripe':
                return $this->processStripePayment($order, $paymentToken);
            case 'paypal':
                return $this->processPayPalPayment($order, $paymentToken);
            default:
                return ['success' => false, 'error' => 'Invalid payment method'];
        }
    }

    /**
     * Process Stripe payment.
     */
    private function processStripePayment(Order $order, string $paymentToken): array
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $paymentIntent = PaymentIntent::retrieve($paymentToken);

            if ($paymentIntent->status === 'succeeded') {
                return [
                    'success' => true,
                    'payment_id' => $paymentIntent->id,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Payment not completed',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process PayPal payment (placeholder).
     */
    private function processPayPalPayment(Order $order, string $paymentToken): array
    {
        // This is a placeholder for PayPal integration
        // In a real application, you would integrate with PayPal SDK
        
        return [
            'success' => true,
            'payment_id' => 'paypal_' . $paymentToken,
        ];
    }

    /**
     * Handle successful payment webhook.
     */
    private function handleSuccessfulPayment($paymentIntent): void
    {
        $order = Order::where('payment_id', $paymentIntent->id)->first();
        
        if ($order && $order->payment_status !== 'paid') {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
            ]);

            // Send order confirmation email
            // Mail::to($order->user->email)->send(new OrderConfirmationMail($order));
        }
    }

    /**
     * Handle failed payment webhook.
     */
    private function handleFailedPayment($paymentIntent): void
    {
        $order = Order::where('payment_id', $paymentIntent->id)->first();
        
        if ($order) {
            $order->update([
                'payment_status' => 'failed',
                'status' => 'cancelled',
            ]);

            // Restore product stock
            foreach ($order->items as $item) {
                if ($item->product && $item->product->manage_stock) {
                    $item->product->increment('stock_quantity', $item->quantity);
                }
            }
        }
    }
}
