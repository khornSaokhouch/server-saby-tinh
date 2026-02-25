<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductItem;
use App\Models\Stock;
use App\Models\ProductItemVariant;
use App\Services\ImageKitService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    private ImageKitService $imageKit;

    public function __construct(ImageKitService $imageKit)
    {
        $this->imageKit = $imageKit;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Product::with(['store', 'category.promotions', 'images', 'items.variants'])
                ->addSelect(['*', 'reviews_avg_rating' => \App\Models\UserReview::selectRaw('avg(rating)')
                    ->join('order_lines', 'user_reviews.order_line_id', '=', 'order_lines.id')
                    ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
                    ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
                    ->whereColumn('product_items.product_id', 'products.id')
                ])
                ->withCount(['items as reviews_count' => function($q) {
                    $q->join('product_item_variants', 'product_items.id', '=', 'product_item_variants.product_item_id')
                      ->join('order_lines', 'product_item_variants.id', '=', 'order_lines.product_item_variant_id')
                      ->join('user_reviews', 'order_lines.id', '=', 'user_reviews.order_line_id');
                }]);

            if ($user && $user->role === 'owner') {
                $query->whereHas('store', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            // Filter by store
            if ($request->filled('store_id')) {
                $query->where('store_id', $request->store_id);
            }

            // Filter by category
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by brand
            if ($request->filled('brand_id')) {
                $query->where('brand_id', $request->brand_id);
            }

            // Search by product name or brand name
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhereHas('brand', function ($bq) use ($search) {
                          $bq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Filter by price range
            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // Filter by Color (Checks through items -> variants)
            if ($request->filled('color_id')) {
                $query->whereHas('items.variants', function ($q) use ($request) {
                    $q->where('color_id', $request->color_id);
                });
            }

            // Filter by Size (Checks through items -> variants)
            if ($request->filled('size_id')) {
                $query->whereHas('items.variants', function ($q) use ($request) {
                    $q->where('size_id', $request->size_id);
                });
            }

            // Filter by Promotion (Checks if product's category has active promotions)
            if ($request->boolean('has_promotion')) {
                $query->whereHas('category.promotions', function ($q) {
                    $now = now();
                    $q->where(function ($sub) use ($now) {
                        $sub->whereNull('start_date')
                            ->orWhere('start_date', '<=', $now);
                    })->where(function ($sub) use ($now) {
                        $sub->whereNull('end_date')
                            ->orWhere('end_date', '>=', $now);
                    });
                });
            }

            if ($request->filled('limit')) {
                $query->limit($request->limit);
            }

            $products = $query->latest()->get();

            return response()->json([
                'success' => true,
                'data' => $products
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'store_id'    => 'required|exists:stores,id',
            'category_id' => 'required|exists:categories,id',
            'brand_id'    => 'required|exists:brands,id',
            'type_id'     => 'required|exists:types,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'nullable|numeric',
            'images'      => 'nullable|array',
            'images.*'    => 'image|mimes:jpeg,png,jpg|max:2048',
            'sku'         => 'required|string|unique:product_items,sku',
            'quantity'    => 'required|integer|min:0',
            'color_id'    => 'nullable|exists:colors,id',
            'size_id'     => 'nullable|exists:sizes,id',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $product = Product::create($request->only([
                    'store_id', 'category_id', 'brand_id', 'type_id', 'name', 'description', 'price'
                ]));

                // Create ProductItem
                $productItem = ProductItem::create([
                    'product_id' => $product->id,
                    'sku'        => $request->sku,
                    'base_price' => $request->price ?? 0,
                    'quantity_in_stock' => $request->quantity,
                    'status'     => true,
                ]);

                // Create ProductItemVariant (Color/Size)
                ProductItemVariant::create([
                    'product_item_id'   => $productItem->id,
                    'color_id'          => $request->color_id,
                    'size_id'           => $request->size_id,
                    'quantity_in_stock' => $request->quantity,
                    'status'            => true,
                ]);

                // Create Stock
                Stock::create([
                    'store_id'        => $request->store_id,
                    'product_item_id' => $productItem->id,
                ]);

                // Handle ImageKit uploads
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $index => $imageFile) {
                        $url = $this->imageKit->upload(
                            $imageFile,
                            'product_' . $product->id . '_' . time() . '_' . $index,
                            'products'
                        );

                        ProductImage::create([
                            'product_id' => $product->id,
                            'image'      => $url,
                            'is_primary' => ($index === 0)
                        ]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Product created successfully',
                    'data'    => $product->load(['images', 'items'])
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($idOrSlug): JsonResponse
    {
        try {
            // Try by ID first if numeric
            $productQuery = Product::with(['store', 'category.promotions', 'brand', 'type', 'images', 'items.variants.color', 'items.variants.size'])
                ->addSelect(['*', 'reviews_avg_rating' => \App\Models\UserReview::selectRaw('avg(rating)')
                    ->join('order_lines', 'user_reviews.order_line_id', '=', 'order_lines.id')
                    ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
                    ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
                    ->whereColumn('product_items.product_id', 'products.id')
                ])
                ->withCount(['items as reviews_count' => function($q) {
                    $q->join('product_item_variants', 'product_items.id', '=', 'product_item_variants.product_item_id')
                      ->join('order_lines', 'product_item_variants.id', '=', 'order_lines.product_item_variant_id')
                      ->join('user_reviews', 'order_lines.id', '=', 'user_reviews.order_line_id');
                }]);

            $product = is_numeric($idOrSlug) 
                ? (clone $productQuery)->find($idOrSlug)
                : null;
            
            // If not found by ID or if not numeric, try by name (slugified comparison)
            if (!$product) {
                // Search for name matching the slug (replace hyphens with spaces)
                $product = (clone $productQuery)
                    ->where('name', 'like', str_replace('-', ' ', $idOrSlug))
                    ->first();
            }

            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Product not found'], 404);
            }

            return response()->json(['success' => true, 'data' => $product], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'price'       => 'nullable|numeric',
            'description' => 'nullable|string',
            'images'      => 'nullable|array',
            'images.*'    => 'image|mimes:jpeg,png,jpg|max:2048',
            'sku'         => 'sometimes|string|unique:product_items,sku,' . ($product->items()->first()->id ?? 0),
            'quantity'    => 'sometimes|integer|min:0',
            'color_id'    => 'nullable|exists:colors,id',
            'size_id'     => 'nullable|exists:sizes,id',
        ]);

        try {
            return DB::transaction(function () use ($request, $product) {
                $product->update($request->only(['name', 'description', 'price', 'status']));

                if ($request->has('sku') || $request->has('quantity') || $request->has('color_id') || $request->has('size_id')) {
                    $item = $product->items()->first();
                    
                    if (!$item) {
                        $item = ProductItem::create([
                            'product_id' => $product->id,
                            'sku'        => $request->sku ?? ('SKU-' . $product->id . '-' . time()),
                            'base_price' => $request->price ?? ($product->price ?? 0),
                            'quantity_in_stock' => $request->quantity ?? 0,
                            'status'     => true,
                        ]);
                        
                        // Create associated Stock record
                        Stock::create([
                            'store_id'        => $product->store_id,
                            'product_item_id' => $item->id,
                        ]);
                    } else {
                        $item->update([
                            'sku' => $request->sku ?? $item->sku,
                            'quantity_in_stock' => $request->quantity ?? $item->quantity_in_stock,
                            'base_price' => $request->price ?? $item->base_price,
                        ]);
                    }

                    // Update or Create Variant
                    $variant = $item->variants()->first();
                    $variantData = [
                        'color_id' => $request->color_id ?? ($variant->color_id ?? null),
                        'size_id'  => $request->size_id ?? ($variant->size_id ?? null),
                        'quantity_in_stock' => $request->quantity ?? ($variant->quantity_in_stock ?? $item->quantity_in_stock),
                    ];

                    if ($variant) {
                        $variant->update($variantData);
                    } else {
                        $item->variants()->create(array_merge($variantData, ['status' => true]));
                    }
                }

                if ($request->hasFile('images')) {
                // Optionally delete old images if logic requires replacement
                // For now, just append new images
                foreach ($request->file('images') as $index => $imageFile) {
                    $url = $this->imageKit->upload(
                        $imageFile,
                        'product_' . $product->id . '_' . time() . '_' . $index,
                        'products'
                    );

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image'      => $url,
                        'is_primary' => false
                    ]);
                }
            }

                return response()->json([
                    'success' => true,
                    'message' => 'Product updated successfully',
                    'data'    => $product->load('images')
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Product not found'], 404);
            }

            // Delete images from ImageKit
            $images = ProductImage::where('product_id', $id)->get();
            foreach ($images as $img) {
                $this->imageKit->delete($img->image);
            }

            $product->delete();

            return response()->json(['success' => true, 'message' => 'Product deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
