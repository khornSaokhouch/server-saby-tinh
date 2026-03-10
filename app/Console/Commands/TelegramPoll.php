<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\TelegramController;
use Illuminate\Http\Request;

class TelegramPoll extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'telegram:poll';

    /**
     * The console command description.
     */
    protected $description = 'Poll Telegram API for updates and process via Webhook controller (for local testing)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token) {
            $this->error('TELEGRAM_BOT_TOKEN not found in .env');
            return;
        }

        $this->info('Starting Telegram Long Polling (Ctrl+C to stop)...');
        $offset = 0;

        while (true) {
            try {
                // We use a timeout slightly LONGER than the Telegram offset timeout (30s)
                $response = Http::timeout(35)->get("https://api.telegram.org/bot{$token}/getUpdates", [
                    'offset' => $offset,
                    'timeout' => 30, // Telegram side timeout
                ]);

                if ($response->successful()) {
                    $updates = $response->json('result');

                    foreach ($updates as $update) {
                        $offset = $update['update_id'] + 1;
                        $this->info("Processing Update ID: {$update['update_id']}");

                        $controller = new TelegramController();
                        $request = new Request([], [], [], [], [], [], json_encode($update));
                        $request->setMethod('POST');
                        $request->headers->set('Content-Type', 'application/json');
                        
                        $controller->webhook($request);
                    }
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // Most likely a timeout, which is EXPECTED in long polling
                // We don't print an error to keep the console clean
                continue;
            } catch (\Exception $e) {
                $this->error('Error during polling: ' . $e->getMessage());
                sleep(2);
            }
        }
    }
}
