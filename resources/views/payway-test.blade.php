<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
    <title>PayWay Terminal | Testing Environment</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    
    
    
    <style>
        :root {
            --aba-blue: #005aab;
            --aba-pink: #ec008c;
            --aba-cyan: #00b5e5;
            --bg-gradient: linear-gradient(135deg, #f8fafd 0%, #eef2f7 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --text-primary: #1a1f24;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 15px -3px rgba(0,0,0,0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0,0,0,0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 2rem 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .terminal-container {
            max-width: 1100px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            align-items: start;
        }

        @media (max-width: 900px) {
            .terminal-container {
                grid-template-columns: 1fr;
            }
        }

        header {
            text-align: center;
            margin-bottom: 2rem;
            grid-column: 1 / -1;
        }

        .column-left, .column-right {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .column-right {
            position: sticky;
            top: 2rem;
        }

        .logo-text {
            font-family: 'Outfit', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(90deg, var(--aba-blue), var(--aba-pink));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            width: 100%;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(248, 250, 252, 0.5);
        }

        .card-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--aba-blue);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Summary Section */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .summary-item {
            background: #f8fafc;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 1px solid #edf2f7;
        }

        .summary-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .summary-value {
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-primary);
        }

        /* Buttons */
        .btn {
            cursor: pointer;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.25rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
        }

        .btn-primary {
            background: var(--aba-blue);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 90, 171, 0.2);
        }

        .btn-primary:hover {
            background: #004a8d;
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(0, 90, 171, 0.3);
        }

        .btn-pink {
            background: var(--aba-pink);
            color: white;
            text-decoration: none;
        }

        .btn-pink:hover {
            background: #d6007e;
        }

        .btn-outline {
            background: white;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }

        /* API Controls Layout */
        .api-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .hidden { display: none !important; }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--aba-blue);
            box-shadow: 0 0 0 3px rgba(0, 90, 171, 0.1);
        }

        /* QR Result */
        .qr-container {
            display: none;
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        #qr-image {
            max-width: 250px;
            width: 100%;
            height: auto;
            margin: 1.5rem 0;
            padding: 1rem;
            border: 1px solid #eee;
            border-radius: 12px;
            background: #fff;
        }

        /* JSON Result Panel */
        .result-panel {
            display: none;
            background: #0f172a;
            color: #e2e8f0;
            padding: 1.5rem;
            border-radius: 16px;
            font-size: 0.85rem;
            overflow-x: auto;
            box-shadow: var(--shadow-lg);
            border: 1px solid #1e293b;
        }

        .result-panel pre {
            white-space: pre-wrap;
            word-break: break-all;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            line-height: 1.5;
            color: #94a3b8;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #dcfce7;
            color: #166534;
        }

        /* Utility */
        hr { border: 0; border-top: 1px solid var(--border-color); margin: 1.25rem 0; }
        
        .test-accounts {
            background: #fffafa;
            border: 1px dashed var(--aba-pink);
            padding: 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            color: #c40074;
        }

    </style>
</head>
<body>
    <div class="terminal-container">
        <header>
            <div class="logo-text">PayWay Terminal</div>
            <p style="font-size: 1rem; color: var(--text-secondary); margin-top: 0.5rem;">Secure Testing Sandbox Environment</p>
        </header>

        <!-- Column Left: Controls -->
        <div class="column-left">
            <!-- Transaction Summary Card -->
            <main class="card">
                <div class="card-header">
                    <span class="card-title">Transaction Summary</span>
                    <span class="status-badge">SANDBOX</span>
                </div>
                <div class="card-body">
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-label">Amount</div>
                            <div class="summary-value" style="color: var(--aba-pink); font-size: 1.2rem;">${{ $amount }}</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Currency</div>
                            <div class="summary-value">{{ $currency }}</div>
                        </div>
                    </div>

                    <form method="POST" target="aba_webservice" id="aba_merchant_request" action="{{ $base_url }}/api/payment-gateway/v1/payments/purchase">
                        <input type="hidden" name="hash" value="{{ $hash }}" />
                        <input type="hidden" name="tran_id" value="{{ $tran_id }}" />
                        <input type="hidden" name="amount" value="{{ $amount }}" />
                        <input type="hidden" name="merchant_id" value="{{ $merchant_id }}" />
                        <input type="hidden" name="req_time" value="{{ $req_time }}" />
                        <input type="hidden" name="payment_option" value="{{ $payment_option }}" />
                        <input type="hidden" name="currency" value="{{ $currency }}" />
                        <input type="hidden" name="firstname" value="{{ $firstname }}" />
                        <input type="hidden" name="lastname" value="{{ $lastname }}" />
                        <input type="hidden" name="phone" value="{{ $phone }}" />
                        <input type="hidden" name="email" value="{{ $email }}" />
                        <input type="hidden" name="return_url" value="{{ $return_url }}" />
                        <input type="hidden" name="cancel_url" value="{{ $cancel_url }}" />
                        <input type="hidden" name="continue_success_url" value="{{ $continue_success_url }}" />
                        <input type="hidden" name="custom_fields" value="{{ $custom_fields }}" />
                        <input type="hidden" name="return_params" value="{{ $return_params }}" />

                        <button type="submit" class="btn btn-primary" style="height: 55px; font-size: 1.1rem; border-radius: 12px;">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm4.59-12.42L10 14.17l-2.59-2.58L6 13l4 4 8-8z"/></svg>
                            Launch Checkout 2.0 (Popup)
                        </button>
                        <button type="button" onclick="launchDirect()" class="btn btn-primary" style="height: 55px; font-size: 1.1rem; border-radius: 12px; background-color: #6366f1; margin-top: 10px; width: 100%;">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6-6-6z"/></svg>
                            Direct Pay (100% Success)
                        </button>
                    </form>

                    <div class="test-accounts" style="margin-top: 1.5rem;">
                        <div style="font-weight: 700; margin-bottom: 0.25rem;">Payment Accounts:</div>
                        <div>USD: 111 111 111 | KHR: 222 222 222</div>
                    </div>
                </div>
            </main>

            <!-- API Testing Card -->
            <section class="card">
                <div class="card-header">
                    <span class="card-title">Server API Tests</span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Target Transaction ID</label>
                        <input type="text" id="target_tran_id" value="{{ $tran_id }}" class="form-control" placeholder="T123456789">
                    </div>

                    <div class="api-grid">
                        <button onclick="checkTransactionV2()" class="btn btn-outline" title="Check transaction status">Check V2</button>
                        <button onclick="closeTransaction()" class="btn btn-outline" style="color: #f97316;" title="Close transaction">Close Txn</button>
                        <button onclick="generateQr('USD')" class="btn btn-outline" title="Generate USD QR Code">QR (USD)</button>
                        <button onclick="generateQr('KHR')" class="btn btn-outline" title="Generate KHR QR Code">QR (KHR)</button>
                        <button onclick="refund()" class="btn btn-outline" style="color: #ef4444;" title="Process $0.10 refund">Refund</button>
                        <button onclick="testPurchase()" class="btn btn-outline" style="color: #6366f1;" title="Server-side purchase API test">Purchase API</button>
                        <button type="button" onclick="submitDirect()" class="btn btn-danger" style="color: #ef4444;">Debug Direct POS</button>
                        <button type="button" onclick="mockSuccess()" class="btn btn-success" style="color: #10b981;">Mock Success</button>
                    </div>
                </div>
            </section>
        </div>

        <!-- Column Right: Results -->
        <div class="column-right">
            <!-- Dynamic Content (Empty by default) -->
            <div id="qr-container" class="qr-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
                    <span style="font-weight: 600; color: var(--aba-blue); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">Payment QR Code</span>
                    <span class="status-badge" style="background: #e0f2fe; color: #0369a1;">PENDING</span>
                </div>
                <h3 id="qr-title" style="color: var(--text-primary); font-family: 'Outfit', sans-serif;">KHQR Merchant Pay</h3>
                <img id="qr-image" src="" alt="ABA KHQR">
                <div style="margin-bottom: 2rem;">
                    <div id="qr-amount-text" style="font-weight: 700; font-size: 2rem; color: var(--aba-blue);"></div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr; gap: 0.75rem;">
                    <a id="deeplink-btn" href="#" class="btn btn-pink" style="height: 50px;">
                        Open in ABA Mobile
                    </a>
                </div>
            </div>

            <!-- Transaction Status Result -->
            <div id="status-container" class="card" style="display: none; box-shadow: var(--shadow-md); border: 1px solid var(--border-color);">
                <div class="card-header" style="background: rgba(248, 250, 252, 1);">
                    <span class="card-title" style="font-size: 0.9rem;">Transaction Status</span>
                    <span id="status-code-badge" class="status-badge">CODE --</span>
                </div>
                <div class="card-body" style="padding: 1.25rem;">
                    <div id="status-msg" style="font-weight: 700; font-size: 1.1rem; margin-bottom: 0.5rem; color: var(--text-primary);"></div>
                    <div style="font-size: 0.85rem; color: var(--text-secondary);">
                        <span id="status-tran-id"></span>
                    </div>
                </div>
            </div>

            <div id="result-panel" class="result-panel">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid #1e293b; padding-bottom: 0.75rem;">
                    <div style="display: flex; gap: 6px;">
                        <span style="width: 12px; height: 12px; border-radius: 50%; background: #ff5f56;"></span>
                        <span style="width: 12px; height: 12px; border-radius: 50%; background: #ffbd2e;"></span>
                        <span style="width: 12px; height: 12px; border-radius: 50%; background: #27c93f;"></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span style="font-weight: 600; color: #64748b; font-size: 0.7rem; text-transform: uppercase;">payway_response.json</span>
                        <button onclick="copyResult()" style="background: #1e293b; border: 1px solid #334155; color: #94a3b8; padding: 4px 10px; border-radius: 4px; font-size: 0.7rem; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                            Copy
                        </button>
                    </div>
                </div>
                <pre id="result-content" style="max-height: 500px; overflow-y: auto;">// No transactions yet...</pre>
            </div>
            
            <div id="welcome-message" style="text-align: center; color: var(--text-secondary); padding: 4rem 2rem; border: 2px dashed var(--border-color); border-radius: 16px; background: #fafafa;">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" style="margin-bottom: 1rem; color: #cbd5e1;"><path d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">Terminal Ready</div>
                <p style="font-size: 0.85rem;">Run an API action or launch Checkout to see results here.</p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('aba_merchant_request').addEventListener('submit', function (event) {
            event.preventDefault();
            
            console.log('Submit clicked. Scanning for PayWay object...');
            
            // The logs finally revealed the object name: abaCheckoutInclude
            const payway = window.abaCheckoutInclude || window.AbaPayway || window.AbaPayWay;
            
            if (payway && typeof payway.checkout === 'function') {
                console.log('PayWay object found:', payway);
                try {
                    payway.checkout();
                    console.log('checkout() popup triggered.');
                } catch (err) {
                    console.error('Popup failed, falling back to direct:', err);
                    launchDirect();
                }
            } else {
                console.warn('PayWay plugin not active. Falling back to direct redirect...');
                launchDirect();
            }
        });

        // Ensure this is in global scope
        function launchDirect() {
            console.log('Direct Pay clicked. Bypassing JS plugin...');
            const form = document.getElementById('aba_merchant_request');
            form.target = "_blank"; // New tab is more reliable for sandbox redirects
            form.submit();
        }

        async function apiCall(url, method = 'GET', body = null) {
            const resultPanel = document.getElementById('result-panel');
            const resultContent = document.getElementById('result-content');
            const qrContainer = document.getElementById('qr-container');
            const statusContainer = document.getElementById('status-container');
            const welcomeMessage = document.getElementById('welcome-message');
            
            welcomeMessage.style.display = 'none';
            resultPanel.style.display = 'block';
            resultContent.textContent = '// Sending request to PayWay API...';
            qrContainer.style.display = 'none';
            statusContainer.style.display = 'none';

            try {
                const options = { method };
                let fullUrl = url;

                if (body) {
                    if (method === 'GET') {
                        fullUrl += '?' + new URLSearchParams(body);
                    } else {
                        options.body = JSON.stringify(body);
                        options.headers = { 'Content-Type': 'application/json' };
                    }
                }
                
                const response = await fetch(fullUrl, options);
                const data = await response.json();
                resultContent.textContent = JSON.stringify(data, null, 2);

                // Handle QR Display
                if (data.qrImage) {
                    qrContainer.style.display = 'block';
                    document.getElementById('qr-image').src = data.qrImage;
                    document.getElementById('qr-amount-text').textContent = `${data.amount} ${data.currency}`;
                    document.getElementById('deeplink-btn').href = data.abapay_deeplink;
                }

                // Handle Status Display
                if (data.status) {
                    statusContainer.style.display = 'block';
                    const s = data.status;
                    document.getElementById('status-msg').textContent = s.message;
                    document.getElementById('status-code-badge').textContent = `CODE ${s.code}`;
                    document.getElementById('status-tran-id').textContent = s.tran_id ? `Tran ID: ${s.tran_id}` : '';
                    
                    const badge = document.getElementById('status-code-badge');
                    if (s.code == 0) {
                        badge.style.background = '#dcfce7'; badge.style.color = '#166534';
                    } else {
                        badge.style.background = '#fee2e2'; badge.style.color = '#991b1b';
                    }
                }
            } catch (error) {
                resultContent.textContent = 'Error: ' + error.message;
            }
        }

        function copyResult() {
            const content = document.getElementById('result-content').textContent;
            navigator.clipboard.writeText(content).then(() => {
                alert('Response copied to clipboard!');
            });
        }

        function checkTransactionV2() {
            const tranId = document.getElementById('target_tran_id').value;
            apiCall("{{ route('aba.test.check') }}", 'GET', { tran_id: tranId });
        }

        function closeTransaction() {
            const tranId = document.getElementById('target_tran_id').value;
            apiCall("{{ route('aba.test.close') }}", 'GET', { tran_id: tranId });
        }

        function generateQr(currency = 'USD') {
            apiCall("{{ route('aba.test.generate-qr') }}", 'GET', { currency });
        }

        function refund() {
            const tranId = document.getElementById('target_tran_id').value;
            apiCall("{{ route('aba.test.refund') }}", 'POST', { tran_id: tranId, amount: '0.10' });
        }

        function testPurchase() {
            apiCall("{{ route('aba.test.purchase') }}", 'GET');
        }

        function submitDirect() {
            const form = document.getElementById('aba_merchant_request');
            form.target = "_blank"; // Open in new tab to see full error
            form.submit();
        }
    </script>
    <!-- Dynamic PayWay Script Loader -->
    <script>
        (function() {
            const urls = [
                'https://checkout-sandbox.payway.com.kh/plugins/checkout2-0.js',
                'https://checkout-sandbox.payway.com.kh/plugins/checkout-2.0.js',
                'https://checkout.payway.com.kh/plugins/checkout2-0.js'
            ];
            
            let loaded = false;
            function loadScript(index) {
                if (index >= urls.length || loaded) return;
                
                console.log(`Attempting to load PayWay script from: ${urls[index]}`);
                const script = document.createElement('script');
                script.src = urls[index];
                script.onload = () => {
                    loaded = true;
                    console.log('PayWay Script Loaded successfully from: ' + urls[index]);
                };
                script.onerror = () => {
                    console.warn('Failed to load PayWay script from: ' + urls[index]);
                    loadScript(index + 1);
                };
                document.body.appendChild(script);
            }
            
            loadScript(0);
        })();

        function mockSuccess() {
            const welcomeMessage = document.getElementById('welcome-message');
            const resultPanel = document.getElementById('result-panel');
            const resultContent = document.getElementById('result-content');
            
            // 1. Hide welcome message and reveal results panel
            welcomeMessage.style.display = 'none';
            resultPanel.classList.remove('hidden');
            
            // 2. Clear previous results
            resultContent.innerHTML = '';
            
            // 3. Create a beautiful, branded success view
            const successDiv = document.createElement('div');
            successDiv.style.textAlign = 'center';
            successDiv.style.padding = '2rem 1rem';
            successDiv.style.animation = 'scaleIn 0.5s ease-out';
            
            successDiv.innerHTML = `
                <div style="background: rgba(16, 185, 129, 0.1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <svg width="48" height="48" fill="none" stroke="#10b981" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                </div>
                <h2 style="color: #10b981; font-weight: 700; margin-bottom: 0.5rem; font-family: 'Outfit', sans-serif;">Payment Successful!</h2>
                <p style="color: var(--text-secondary); margin-bottom: 2rem;">Transaction ID: TEST_MOCK_${Date.now()}</p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; text-align: left; margin-bottom: 2rem; background: #f8fafc; padding: 1.25rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <div>
                        <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase;">Amount Paid</div>
                        <div style="font-weight: 700; color: var(--aba-pink); font-size: 1.1rem;">$0.10</div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase;">Payment Method</div>
                        <div style="font-weight: 700; color: var(--aba-blue);">ABA Pay / Cards</div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase;">Status</div>
                        <div style="font-weight: 700; color: #10b981;">APPROVED</div>
                    </div>
                    <div>
                        <div style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase;">Auth Code</div>
                        <div style="font-weight: 700; color: var(--text-primary);">A2279934</div>
                    </div>
                </div>
                
                <button onclick="location.reload()" class="btn btn-outline" style="max-width: 200px;">Back to Terminal</button>
                <p style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2rem; font-style: italic;">Note: This is a 100% logic simulation to confirm UI success during localhost testing.</p>
            `;
            
            resultContent.appendChild(successDiv);
            
            // Add scaleIn animation to CSS if not present
            if (!document.getElementById('mock-success-anim')) {
                const style = document.createElement('style');
                style.id = 'mock-success-anim';
                style.textContent = `
                    @keyframes scaleIn {
                        from { transform: scale(0.9); opacity: 0; }
                        to { transform: scale(1); opacity: 1; }
                    }
                `;
                document.head.appendChild(style);
            }
        }
    </script>
</body>
</html>
