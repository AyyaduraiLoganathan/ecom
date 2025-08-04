<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): View
    {
        $query = Product::active()->inStock()->with(['category', 'reviews']);

        // Filter by category
        if ($request->has('category') && $request->category) {
            $category = Category::where('slug', $request->category)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }

        // Filter by price range
        if ($request->has('min_price') && $request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price') && $request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        // Filter by rating
        if ($request->has('rating') && $request->rating) {
            $query->where('average_rating', '>=', $request->rating);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Sort
        $sortBy = $request->get('sort', 'name');
        $sortOrder = $request->get('order', 'asc');

        switch ($sortBy) {
            case 'price':
                $query->orderBy('price', $sortOrder);
                break;
            case 'rating':
                $query->orderBy('average_rating', $sortOrder);
                break;
            case 'popularity':
                $query->orderBy('views_count', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('name', $sortOrder);
        }

        $products = $query->paginate(config('app.products_per_page', 12));

        // Get categories for filter sidebar
        $categories = Category::active()
            ->parents()
            ->withCount('activeProducts')
            ->having('active_products_count', '>', 0)
            ->orderBy('name')
            ->get();

        // Get price range
        $priceRange = Product::active()->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();

        return view('frontend.products.index', compact(
            'products',
            'categories',
            'priceRange'
        ));
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): View
    {
        // Increment views count
        $product->incrementViews();

        // Load relationships
        $product->load(['category', 'reviews.user']);

        // Get related products
        $relatedProducts = Product::active()
            ->inStock()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();

        // Get recent reviews
        $recentReviews = $product->reviews()
            ->approved()
            ->with('user')
            ->latest()
            ->limit(5)
            ->get();

        // Get rating distribution
        $ratingDistribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = $product->reviews()->approved()->where('rating', $i)->count();
            $percentage = $product->reviews_count > 0 ? ($count / $product->reviews_count) * 100 : 0;
            $ratingDistribution[$i] = [
                'count' => $count,
                'percentage' => round($percentage, 1),
            ];
        }

        return view('frontend.products.show', compact(
            'product',
            'relatedProducts',
            'recentReviews',
            'ratingDistribution'
        ));
    }

    /**
     * Get product quick view data (AJAX).
     */
    public function quickView(Product $product): JsonResponse
    {
        $product->load('category');

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->formatted_price,
                'original_price' => $product->is_on_sale ? $product->formatted_original_price : null,
                'discount_percentage' => $product->discount_percentage,
                'short_description' => $product->short_description,
                'image' => $product->image_url,
                'gallery' => $product->all_images,
                'category' => $product->category->name,
                'rating' => $product->average_rating,
                'reviews_count' => $product->reviews_count,
                'stock_status' => $product->stock_status,
                'is_available' => $product->is_available,
                'attributes' => $product->attributes,
                'url' => route('products.show', $product->slug),
            ],
        ]);
    }

    /**
     * Get products by category (AJAX).
     */
    public function getByCategory(Category $category): JsonResponse
    {
        $products = $category->activeProducts()
            ->inStock()
            ->with('category')
            ->limit(12)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->formatted_price,
                    'original_price' => $product->is_on_sale ? $product->formatted_original_price : null,
                    'image' => $product->image_url,
                    'rating' => $product->average_rating,
                    'reviews_count' => $product->reviews_count,
                    'url' => route('products.show', $product->slug),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }

    /**
     * Submit a product review.
     */
    public function submitReview(Request $request, Product $product): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You must be logged in to submit a review.',
            ], 401);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Check if user has already reviewed this product
        $existingReview = Review::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already reviewed this product.',
            ], 422);
        }

        // Check if user has purchased this product
        $hasPurchased = auth()->user()->orders()
            ->whereHas('items', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->where('payment_status', 'paid')
            ->exists();

        $review = Review::create([
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'is_verified_purchase' => $hasPurchased,
        ]);

        // Update product rating stats
        $product->updateRatingStats();

        return response()->json([
            'status' => 'success',
            'message' => 'Thank you for your review!',
            'data' => [
                'review' => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'title' => $review->title,
                    'comment' => $review->comment,
                    'reviewer_name' => $review->reviewer_name,
                    'formatted_date' => $review->formatted_date,
                    'is_verified_purchase' => $review->is_verified_purchase,
                ],
                'product_stats' => [
                    'average_rating' => $product->fresh()->average_rating,
                    'reviews_count' => $product->fresh()->reviews_count,
                ],
            ],
        ]);
    }

    /**
     * Get product reviews (AJAX pagination).
     */
    public function getReviews(Product $product, Request $request): JsonResponse
    {
        $query = $product->reviews()->approved()->with('user');

        // Filter by rating
        if ($request->has('rating') && $request->rating) {
            $query->where('rating', $request->rating);
        }

        // Sort
        $sortBy = $request->get('sort', 'newest');
        switch ($sortBy) {
            case 'oldest':
                $query->oldest();
                break;
            case 'highest_rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'lowest_rating':
                $query->orderBy('rating', 'asc');
                break;
            case 'most_helpful':
                $query->orderBy('helpful_count', 'desc');
                break;
            default:
                $query->latest();
        }

        $reviews = $query->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => [
                'reviews' => $reviews->items(),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ],
            ],
        ]);
    }
}
