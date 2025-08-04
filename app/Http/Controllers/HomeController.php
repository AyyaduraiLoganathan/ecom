<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the home page.
     */
    public function index(): View
    {
        // Get featured categories for bento grid
        $featuredCategories = Category::active()
            ->parents()
            ->orderBy('sort_order')
            ->limit(9)
            ->get();

        // Get featured products
        $featuredProducts = Product::active()
            ->featured()
            ->inStock()
            ->with('category')
            ->limit(8)
            ->get();

        // Get popular products (based on views)
        $popularProducts = Product::active()
            ->inStock()
            ->with('category')
            ->orderBy('views_count', 'desc')
            ->limit(8)
            ->get();

        // Get hot selling products (based on order items count)
        $hotSellingProducts = Product::active()
            ->inStock()
            ->with('category')
            ->withCount('orderItems')
            ->orderBy('order_items_count', 'desc')
            ->limit(8)
            ->get();

        return view('frontend.home', compact(
            'featuredCategories',
            'featuredProducts',
            'popularProducts',
            'hotSellingProducts'
        ));
    }

    /**
     * Handle newsletter subscription.
     */
    public function subscribeNewsletter(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        try {
            $subscriber = NewsletterSubscriber::firstOrCreate(
                ['email' => $request->email],
                [
                    'verification_token' => NewsletterSubscriber::generateVerificationToken(),
                    'source' => 'homepage',
                ]
            );

            if ($subscriber->wasRecentlyCreated) {
                // Send verification email (implement mail sending logic)
                // Mail::to($subscriber->email)->send(new NewsletterVerificationMail($subscriber));
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Thank you for subscribing! Please check your email to verify your subscription.',
                ]);
            } else {
                if ($subscriber->is_verified) {
                    return response()->json([
                        'status' => 'info',
                        'message' => 'You are already subscribed to our newsletter.',
                    ]);
                } else {
                    return response()->json([
                        'status' => 'info',
                        'message' => 'Please check your email to verify your subscription.',
                    ]);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong. Please try again later.',
            ], 500);
        }
    }

    /**
     * Verify newsletter subscription.
     */
    public function verifyNewsletter(Request $request, string $token): View
    {
        $subscriber = NewsletterSubscriber::where('verification_token', $token)->first();

        if (!$subscriber) {
            return view('frontend.newsletter-verification', [
                'status' => 'error',
                'message' => 'Invalid verification token.',
            ]);
        }

        if ($subscriber->is_verified) {
            return view('frontend.newsletter-verification', [
                'status' => 'info',
                'message' => 'Your email is already verified.',
            ]);
        }

        $subscriber->markAsVerified();

        return view('frontend.newsletter-verification', [
            'status' => 'success',
            'message' => 'Thank you! Your email has been verified successfully.',
        ]);
    }

    /**
     * Handle newsletter unsubscription.
     */
    public function unsubscribeNewsletter(Request $request, string $email): View
    {
        $subscriber = NewsletterSubscriber::where('email', $email)->first();

        if ($subscriber) {
            $subscriber->unsubscribe();
        }

        return view('frontend.newsletter-unsubscribe', [
            'message' => 'You have been successfully unsubscribed from our newsletter.',
        ]);
    }

    /**
     * Get Instagram feed (placeholder for Instagram API integration).
     */
    public function getInstagramFeed(): JsonResponse
    {
        // This is a placeholder. In a real application, you would:
        // 1. Use Instagram Graph API to fetch posts
        // 2. Cache the results for better performance
        // 3. Handle API rate limits and errors

        $instagramPosts = [
            [
                'id' => '1',
                'media_url' => 'https://via.placeholder.com/300x300?text=Instagram+1',
                'permalink' => 'https://instagram.com/p/example1',
                'caption' => 'Check out our latest products! #ecommerce #shopping',
                'media_type' => 'IMAGE',
            ],
            [
                'id' => '2',
                'media_url' => 'https://via.placeholder.com/300x300?text=Instagram+2',
                'permalink' => 'https://instagram.com/p/example2',
                'caption' => 'Behind the scenes at our warehouse 📦',
                'media_type' => 'IMAGE',
            ],
            [
                'id' => '3',
                'media_url' => 'https://via.placeholder.com/300x300?text=Instagram+3',
                'permalink' => 'https://instagram.com/p/example3',
                'caption' => 'Customer love! Thank you for choosing us ❤️',
                'media_type' => 'IMAGE',
            ],
            [
                'id' => '4',
                'media_url' => 'https://via.placeholder.com/300x300?text=Instagram+4',
                'permalink' => 'https://instagram.com/p/example4',
                'caption' => 'New arrivals coming soon! Stay tuned 🔥',
                'media_type' => 'IMAGE',
            ],
            [
                'id' => '5',
                'media_url' => 'https://via.placeholder.com/300x300?text=Instagram+5',
                'permalink' => 'https://instagram.com/p/example5',
                'caption' => 'Flash sale this weekend! Don\'t miss out 💥',
                'media_type' => 'IMAGE',
            ],
            [
                'id' => '6',
                'media_url' => 'https://via.placeholder.com/300x300?text=Instagram+6',
                'permalink' => 'https://instagram.com/p/example6',
                'caption' => 'Sustainable packaging, better future 🌱',
                'media_type' => 'IMAGE',
            ],
            [
                'id' => '7',
                'media_url' => 'https://via.placeholder.com/300x300?text=Instagram+7',
                'permalink' => 'https://instagram.com/p/example7',
                'caption' => 'Team appreciation post! Our amazing staff 👥',
                'media_type' => 'IMAGE',
            ],
            [
                'id' => '8',
                'media_url' => 'https://via.placeholder.com/300x300?text=Instagram+8',
                'permalink' => 'https://instagram.com/p/example8',
                'caption' => 'Quality products, happy customers 😊',
                'media_type' => 'IMAGE',
            ],
        ];

        return response()->json([
            'status' => 'success',
            'data' => $instagramPosts,
        ]);
    }

    /**
     * Search products (AJAX endpoint).
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'status' => 'success',
                'data' => [],
            ]);
        }

        $products = Product::active()
            ->inStock()
            ->search($query)
            ->with('category')
            ->limit(10)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->formatted_price,
                    'image' => $product->image_url,
                    'category' => $product->category->name,
                    'url' => route('products.show', $product->slug),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }
}
