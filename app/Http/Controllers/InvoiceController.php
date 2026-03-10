<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Invoice::query()
                ->with([
                    'order.user',
                    'order.orderLines.productItemVariant.productItem.product.store',
                    'order.orderLines.productItemVariant.productItem.product.images',
                    'order.userPayments.paymentMethod',
                    'order.paymentMethod',
                    'order.orderStatus',
                    'paymentStatus'
                ])
                ->latest();

            // Filter by Payment Status
            if ($request->filled('status')) {
                $query->where('payment_status_id', $request->status);
            }

            // Filter by Store ID
            if ($request->filled('store_id')) {
                $storeId = $request->store_id;
                $query->whereHas('order.orderLines.productItemVariant.productItem.product', function ($q) use ($storeId) {
                    $q->where('products.store_id', $storeId);
                });
            }

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'LIKE', "%{$search}%")
                      ->orWhereHas('order.user', function ($sub) use ($search) {
                          $sub->where('name', 'LIKE', "%{$search}%");
                      });
                });
            }

            $invoices = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $invoices
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $totalGmv = Invoice::where('payment_status_id', 2)->sum('total_amount');
            $pendingAmount = Invoice::where('payment_status_id', 1)->sum('total_amount');
            $paidCount = Invoice::where('payment_status_id', 2)->count();
            $unpaidCount = Invoice::where('payment_status_id', 1)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_gmv' => (float)$totalGmv,
                    'pending_amount' => (float)$pendingAmount,
                    'paid_count' => $paidCount,
                    'unpaid_count' => $unpaidCount,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get store-wise invoice statistics.
     */
    public function storeStats(): JsonResponse
    {
        try {
            $stats = DB::table('stores')
                ->leftJoin('products', 'stores.id', '=', 'products.store_id')
                ->leftJoin('product_items', 'products.id', '=', 'product_items.product_id')
                ->leftJoin('product_item_variants', 'product_items.id', '=', 'product_item_variants.product_item_id')
                ->leftJoin('order_lines', 'product_item_variants.id', '=', 'order_lines.product_item_variant_id')
                ->leftJoin('shop_orders', 'order_lines.order_id', '=', 'shop_orders.id')
                ->where('shop_orders.payment_status_id', 2)
                ->select(
                    'stores.id',
                    'stores.name',
                    'stores.store_image',
                    DB::raw('COUNT(DISTINCT shop_orders.id) as paid_count'),
                    DB::raw('SUM(order_lines.price * order_lines.quantity) as total_earned')
                )
                ->groupBy('stores.id', 'stores.name', 'stores.store_image')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified invoice.
     */
    public function show($id): JsonResponse
    {
        try {
            $invoice = Invoice::with([
                'order.user', 
                'order.orderLines.productItemVariant.productItem.product.store', 
                'order.orderLines.productItemVariant.productItem.product.images',
                'order.userPayments.paymentMethod', 
                'order.shippingAddress', 
                'order.paymentMethod', 
                'paymentStatus'
            ])
                ->find($id);

            if (!$invoice) {
                return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update invoice status.
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status_id' => 'required|exists:payment_statuses,id'
        ]);

        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        $invoice->payment_status_id = $request->status_id;
        $invoice->save();

        return response()->json([
            'success' => true,
            'message' => 'Invoice status updated successfully',
            'data' => $invoice->load('paymentStatus')
        ]);
    }
}
