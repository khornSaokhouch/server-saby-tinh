<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserSocial;
use App\Models\User;
use App\Models\ShopOrder;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class TelegramController extends Controller
{
    /**
     * Handle Telegram Webhook updates.
     */
    public function webhook(Request $request)
    {
        $update = $request->all();
        Log::info('Telegram Webhook Hit:', $update);

        if (isset($update['message']['text'])) {
            $text = trim($update['message']['text']);
            $chatId = $update['message']['chat']['id'];
            $telegramUserId = $update['message']['from']['id'];

            // 1. Handle /start {user_id} (Linking)
            if (preg_match('/^\/start\s+(\d+)$/', $text, $matches)) {
                return $this->handleLinking($chatId, $telegramUserId, $matches[1]);
            }

            // 2. Check if user is linked for subsequent commands
            $userSocial = UserSocial::where('provider', 'telegram')
                                    ->where('social_id', $telegramUserId)
                                    ->first();

            if (!$userSocial) {
                self::sendMessage($chatId, "❌ Your Telegram is not linked to any account.\n\nPlease link it first from your profile or use the link provided by the app.");
                return response()->json(['status' => 'not_linked']);
            }

            $user = $userSocial->user;

            // 3. Handle /orders command
            if ($text === '/orders') {
                return $this->sendOrders($chatId, $user);
            }

            // 4. Default: Provide Auto-Login link
            $token = JWTAuth::fromUser($user);
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            $loginUrl = $frontendUrl . "/auth/callback?token=" . $token;

            $message = "👋 Hello {$user->name}!\n\n";
            $message .= "Click the link below to login to your dashboard instantly:\n\n";
            $message .= "[🚀 Login to Saby-Tinh]({$loginUrl})";

            self::sendMessage($chatId, $message);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle account linking logic.
     */
    private function handleLinking($chatId, $telegramUserId, $userId)
    {
        $user = User::find($userId);

        if ($user) {
            UserSocial::updateOrCreate(
                ['provider' => 'telegram', 'social_id' => $telegramUserId],
                ['user_id' => $user->id]
            );

            Log::info("Telegram ID {$telegramUserId} linked to User ID {$userId}");
            self::sendMessage($chatId, "🎉 *Congratulations!* Your account is now successfully linked to Saby-Tinh.\n\nNow you can:\n📦 Use /orders to check recent store sales\n🚀 Send any message to get an instant login link");
            return response()->json(['status' => 'linked']);
        }

        self::sendMessage($chatId, "❌ Invalid user ID. Linking failed.");
        return response()->json(['status' => 'invalid_user']);
    }

    /**
     * Send recent orders to the user.
     */
    private function sendOrders($chatId, $user)
    {
        $user->load('store');
        
        if (!$user->store) {
            self::sendMessage($chatId, "❌ You don't have a store assigned to your account.");
            return response()->json(['status' => 'no_store']);
        }

        $storeId = $user->store->id;

        // Fetch last 5 orders for this store
        $orders = ShopOrder::whereHas('orderLines.productItemVariant.productItem.product', function($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })
        ->with(['orderStatus', 'paymentStatus'])
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

        if ($orders->isEmpty()) {
            self::sendMessage($chatId, "📦 No orders found for your store yet.");
            return response()->json(['status' => 'no_orders']);
        }

        $message = "📋 *Recent Orders for {$user->store->name}*\n\n";

        foreach ($orders as $order) {
            $status = $order->orderStatus->status ?? 'Unknown';
            $total = number_format($order->order_total, 2);
            $date = $order->created_at->format('M d, H:i');
            
            $message .= "🔹 *Order #{$order->id}*\n";
            $message .= "💰 Total: \${$total}\n";
            $message .= "🏷 Status: {$status}\n";
            $message .= "📅 Date: {$date}\n\n";
        }

        self::sendMessage($chatId, $message);
        return response()->json(['status' => 'orders_sent']);
    }

    /**
     * Send a message to a Telegram chat.
     */
    public static function sendMessage($chatId, $text)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token) {
            Log::error("TELEGRAM_BOT_TOKEN not found in .env");
            return;
        }

        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            
            Log::info("Telegram response: " . $response);
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram message: " . $e->getMessage());
        }
    }
}
