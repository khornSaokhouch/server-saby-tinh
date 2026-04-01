<?php
use App\Http\Controllers\{AuthController, UserController, CategoryController, TypeController,
     BrandController, StoreController, OrderStatusController, ShippingMethodController, 
     ColorController, SizeController, PaymentAccountController, EventController, PromotionController, 
     PromoCodeController, SellerController, GoogleAuthController, OtpController, CompanyInfoController, 
     CountryController, UserAddressController, ProductController, StockController, ShoppingCartController, 
     ShopOrderController, UserReviewController, PaymentStatusController, UserPaymentController, 
     TelegramController, DashboardController, PromoCodeUsageController, InvoiceController, SearchController, 
     AbaPaywayController, AbaWebhookController, ReportByStoreController, BakongController, PayoutController};
use Illuminate\Support\Facades\Route;

// Public
Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);



Route::get('/countries', [CountryController::class, 'index']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::post('/otp/send', [OtpController::class, 'sendOtp']);
Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);
Route::post('/otp/resend', [OtpController::class, 'resendOtp']);

// Password Reset Flow
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


// Social Login (Google/Facebook)
Route::get('/auth/{provider}/redirect', [GoogleAuthController::class, 'redirectToProvider']);
Route::get('/auth/{provider}/callback', [GoogleAuthController::class, 'handleProviderCallback']);


Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}/products', function ($id) {
    $products = \App\Models\Product::with(['store', 'category.promotions' => function ($q) {
            $q->where('promotions.status', 1);
            $now = now();
            $q->where(function ($sub) use ($now) {
                $sub->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })->where(function ($sub) use ($now) {
                $sub->whereNull('end_date')->orWhere('end_date', '>=', $now);
            });
        }, 'brand', 'type', 'images', 'items.variants'])
        ->addSelect(['*', 'reviews_avg_rating' => \App\Models\UserReview::selectRaw('avg(rating)')
            ->join('order_lines', 'user_reviews.order_line_id', '=', 'order_lines.id')
            ->join('product_item_variants', 'order_lines.product_item_variant_id', '=', 'product_item_variants.id')
            ->join('product_items', 'product_item_variants.product_item_id', '=', 'product_items.id')
            ->whereColumn('product_items.product_id', 'products.id')
        ])
        ->where('category_id', $id)
        ->latest()
        ->get();
    return response()->json(['success' => true, 'data' => $products]);
});
Route::get('/types', [TypeController::class, 'index']);
Route::get('/brands', [BrandController::class, 'index']);
Route::get('/stores', [StoreController::class, 'index']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/colors', [ColorController::class, 'index']);
Route::get('/sizes', [SizeController::class, 'index']);
Route::get('/products/{idOrSlug}', [ProductController::class, 'show']);
Route::get('/events/{idOrName}', [EventController::class, 'show']);
Route::get('/stores/{idOrName}', [StoreController::class, 'show']);
Route::get('/search', [SearchController::class, 'index']);
Route::get('/reviews', [UserReviewController::class, 'index']);
Route::post('/promo-codes/validate', [PromoCodeController::class, 'validateCode']);
Route::get('/user-addresses/all', [UserAddressController::class, 'adminIndex']);

    // PayWay (ABA) Routes
    Route::prefix('payway')->group(function () {
        Route::get('/products', [AbaPaywayController::class, 'showForm']);
        Route::post('/generate-qr', [AbaPaywayController::class, 'generateQr']);
        Route::post('/transaction-detail', [AbaPaywayController::class, 'transactionDetail']);
        Route::post('/check-transaction', [AbaPaywayController::class, 'checkTransaction']);
        Route::post('/refund', [AbaPaywayController::class, 'refund']);
        Route::post('/webhook', [AbaWebhookController::class, 'handle']);
    });


// Authenticated
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAllDevices']);
    Route::post('/sessions/terminate-multiple', [AuthController::class, 'terminateMultipleSessions']);
    Route::delete('/sessions/{id}', [AuthController::class, 'terminateSession']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/user/login-history', [AuthController::class, 'getLoginHistory']);
    Route::get('/user/active-sessions', [AuthController::class, 'getActiveSessions']);
    Route::post('/users/{id}', [UserController::class, 'updateProfile']);
    Route::post('/update-password', [AuthController::class, 'updatePassword']);
    Route::post('/verify-password', [AuthController::class, 'verifyPassword']);

    Route::get('/shipping-methods', [ShippingMethodController::class, 'index']);
    Route::get('/order-statuses', [OrderStatusController::class, 'index']);
    Route::get('/payment-statuses', [PaymentStatusController::class, 'index']);
    Route::get('/payment-accounts', [PaymentAccountController::class, 'index']);
    //Bakong Routes
    Route::post('/generate-qr', [BakongController::class, 'generateQr']);
    Route::post('/check-md5', [BakongController::class, 'checkMd5']);
    
    // Admin & Owner Routes (Corrected space)
    Route::middleware('role:admin,owner')->group(function () {
        // Categories
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::post('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        // Shipping Methods

        Route::get('/shipping-methods/{id}', [ShippingMethodController::class, 'show']);
        Route::post('/shipping-methods', [ShippingMethodController::class, 'store']);
        Route::post('/shipping-methods/{id}', [ShippingMethodController::class, 'update']);
        Route::delete('/shipping-methods/{id}', [ShippingMethodController::class, 'destroy']);

        // Colors
        Route::post('/colors', [ColorController::class, 'store']);
        Route::post('/colors/{id}', [ColorController::class, 'update']);
        Route::delete('/colors/{id}', [ColorController::class, 'destroy']);

        // Sizes

        Route::post('/sizes', [SizeController::class, 'store']);
        Route::post('/sizes/{id}', [SizeController::class, 'update']);
        Route::delete('/sizes/{id}', [SizeController::class, 'destroy']);


        // Order Status

        Route::get('/order-statuses/{id}', [OrderStatusController::class, 'show']);
        Route::post('/order-statuses', [OrderStatusController::class, 'store']);
        Route::post('/order-statuses/{id}', [OrderStatusController::class, 'update']);
        Route::delete('/order-statuses/{id}', [OrderStatusController::class, 'destroy']);
        
        // Brands
        Route::post('/brands', [BrandController::class, 'store']);
        Route::post('/brands/{id}', [BrandController::class, 'update']);
        Route::delete('/brands/{id}', [BrandController::class, 'destroy']);

        // Types
        Route::post('/types', [TypeController::class, 'store']);
        Route::post('/types/{id}', [TypeController::class, 'update']);
        Route::delete('/types/{id}', [TypeController::class, 'destroy']);

         // Store management
        Route::get('/stores/{id}', [StoreController::class, 'show']);
        Route::post('/stores', [StoreController::class, 'store']);
        Route::post('/stores/{id}', [StoreController::class, 'update']);
        Route::delete('/stores/{id}', [StoreController::class, 'destroy']);

        // Company Info management
        Route::get('/companies', [CompanyInfoController::class, 'index']);
        Route::get('/companies/{id}', [CompanyInfoController::class, 'show']);
        Route::post('/companies', [CompanyInfoController::class, 'store']);
        Route::post('/companies/{id}', [CompanyInfoController::class, 'update']);
        Route::delete('/companies/{id}', [CompanyInfoController::class, 'destroy']);
        // Products management
        Route::post('/products/batch-delete', [ProductController::class, 'batchDelete']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::match(['POST', 'PUT'], '/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);

        // Stock management
        Route::get('/stocks', [StockController::class, 'index']);

        // Payment Accounts

        Route::post('/payment-accounts', [PaymentAccountController::class, 'store']);
        Route::post('/payment-accounts/{id}', [PaymentAccountController::class, 'update']);
        Route::delete('/payment-accounts/{id}', [PaymentAccountController::class, 'destroy']);

        // Payment Status
        Route::get('/payment-statuses/{id}', [PaymentStatusController::class, 'show']);
        Route::post('/payment-statuses', [PaymentStatusController::class, 'store']);
        Route::post('/payment-statuses/{id}', [PaymentStatusController::class, 'update']);
        Route::delete('/payment-statuses/{id}', [PaymentStatusController::class, 'destroy']);

        // Reports
        Route::get('/reports/stats', [ReportByStoreController::class, 'stats']);
        Route::get('/reports/recent-orders', [ReportByStoreController::class, 'recentOrders']);
        Route::get('/reports/top-products', [ReportByStoreController::class, 'topProducts']);
        Route::get('/reports/top-customers', [ReportByStoreController::class, 'topCustomers']);
        Route::get('/reports/analytics', [ReportByStoreController::class, 'analytics']);
        Route::get('/reports/dashboard', [ReportByStoreController::class, 'dashboard']);

        // Payouts
        Route::post('/admin/payouts/generate-qr', [BakongController::class, 'generatePayoutQr']);
        Route::post('/payouts/bulk', [PayoutController::class, 'bulkStore']);
        Route::apiResource('payouts', PayoutController::class);
    });

    // Sellers
    Route::post('/sellers', [SellerController::class, 'store']);

    // Admin Only
    Route::middleware('role:admin')->group(function () {
        Route::get('/sellers', [SellerController::class, 'index']);
        Route::get('/sellers/pending-count', [SellerController::class, 'pendingCount']);
        Route::get('/sellers/{id}', [SellerController::class, 'show']);
        Route::post('/sellers/{id}/approve', [SellerController::class, 'approve']);
        Route::post('/sellers/{id}/reject', [SellerController::class, 'reject']);
        Route::delete('/sellers/{id}', [SellerController::class, 'destroy']);

        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']); // Create User
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::patch('/users/{id}/role', [UserController::class, 'updateRole']); // Update User Role
        Route::delete('/users/{id}', [UserController::class, 'destroy']);



        // Countries
        Route::post('/countries', [CountryController::class, 'store']);
        Route::post('/countries/{id}', [CountryController::class, 'update']);
        Route::delete('/countries/{id}', [CountryController::class, 'destroy']);

        Route::post('/events', [EventController::class, 'store']);
        Route::post('/events/{id}', [EventController::class, 'update']);
        Route::delete('/events/{id}', [EventController::class, 'destroy']);

        Route::get('/admin/dashboard', [DashboardController::class, 'index']);
         Route::get('/admin/promo-code-usages', [PromoCodeUsageController::class, 'index']);
        Route::get('/admin/promo-code-usages/stats', [PromoCodeUsageController::class, 'stats']);

        // Invoices
        Route::get('/admin/invoices', [InvoiceController::class, 'index']);
        Route::get('/admin/invoices/stats', [InvoiceController::class, 'stats']);
        Route::get('/admin/invoices/store-stats', [InvoiceController::class, 'storeStats']);
        Route::get('/admin/invoices/{id}', [InvoiceController::class, 'show']);
        Route::post('/admin/invoices/{id}/status', [InvoiceController::class, 'updateStatus']);
    });


    // Promotions (Admin + Owner)
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/promotions', [PromotionController::class, 'index']);
        Route::post('/promotions', [PromotionController::class, 'store']);
        Route::post('/promotions/{id}', [PromotionController::class, 'update']);
        Route::delete('/promotions/{id}', [PromotionController::class, 'destroy']);

        // Promotion Categories Management
        Route::get('/promotion-category/{id}/categories', [PromotionController::class, 'getCategories']);
        Route::post('/promotion-category/{id}/categories', [PromotionController::class, 'attachCategory']);
        Route::delete('/promotion-category/{id}/categories/{categoryId}', [PromotionController::class, 'detachCategory']);

        // Promo Codes
        Route::get('/promo-codes', [PromoCodeController::class, 'index']);
        Route::post('/promo-codes', [PromoCodeController::class, 'store']);
        Route::post('/promo-codes/{id}', [PromoCodeController::class, 'update']);
        Route::delete('/promo-codes/{id}', [PromoCodeController::class, 'destroy']);
    });


    // Shopping Cart
    Route::get('/cart', [ShoppingCartController::class, 'index']);
    Route::post('/cart/add', [ShoppingCartController::class, 'addItem']);
    Route::patch('/cart/items/{id}', [ShoppingCartController::class, 'updateItem']);
    Route::delete('/cart/items/{id}', [ShoppingCartController::class, 'removeItem']);
    Route::delete('/cart', [ShoppingCartController::class, 'clearCart']);

    // Orders
    Route::get('/orders', [ShopOrderController::class, 'index']);
    Route::post('/orders', [ShopOrderController::class, 'store']);
    Route::get('/orders/{id}', [ShopOrderController::class, 'show']);
    Route::post('/orders/{id}/confirm', [ShopOrderController::class, 'confirm']);

    // User Payments
    Route::get('/user-payments', [UserPaymentController::class, 'index']);
    Route::post('/user-payments', [UserPaymentController::class, 'store']);
    Route::get('/user-payments/{id}', [UserPaymentController::class, 'show']);
    Route::match(['POST', 'PUT'], '/user-payments/{id}', [UserPaymentController::class, 'update'])->middleware('role:admin');
    Route::delete('/user-payments/{id}', [UserPaymentController::class, 'destroy'])->middleware('role:admin');

    // Bakong Payments
    Route::post('/bakong/generate-qr', [BakongController::class, 'generateQr']);
    Route::post('/bakong/check-status', [BakongController::class, 'checkMd5']);



    // Reviews
    Route::post('/reviews', [UserReviewController::class, 'store']);

    // User Addresses (authenticated users)
    Route::get('/user-addresses', [UserAddressController::class, 'index']);
    Route::post('/user-addresses', [UserAddressController::class, 'store']);
    Route::patch('/user-addresses/{id}', [UserAddressController::class, 'update']);
    Route::delete('/user-addresses/{id}', [UserAddressController::class, 'destroy']);
});
