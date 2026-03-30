<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ABA Payway Test Gateway</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        :root {
            --primary: #0081a7;
            --secondary: #00afb9;
            --accent: #fed9b7;
            --danger: #f07167;
            --bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: var(--bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(0, 129, 167, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(0, 175, 185, 0.15) 0px, transparent 50%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
        }

        .container {
            max-width: 1000px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 2rem;
        }

        h1 {
            grid-column: span 2;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        h2 {
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        select, input {
            width: 100%;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text);
            outline: none;
            transition: all 0.2s;
        }

        select:focus, input:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 2px rgba(0, 175, 185, 0.2);
        }

        .product-list {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 1rem;
            padding-right: 0.5rem;
        }

        .product-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 0.75rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .product-item:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .product-item.selected {
            background: rgba(0, 175, 185, 0.1);
            border: 1px solid var(--secondary);
        }

        .btn {
            width: 100%;
            padding: 1rem;
            border-radius: 1rem;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 10px 15px -3px rgba(0, 129, 167, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 129, 167, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .qr-display {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 400px;
        }

        .qr-image-container {
            position: relative;
            background: #fff;
            padding: 0;
            border-radius: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            width: 340px;
            color: #1e293b;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .tmpl-header {
            background: #d91b29; /* Official ABA Red */
            color: #fff;
            padding: 1.25rem;
            font-weight: 700;
            font-size: 1.25rem;
            position: relative;
        }

        /* Diagonal cut in header */
        .tmpl-header::after {
            content: '';
            position: absolute;
            bottom: -15px;
            right: 0;
            width: 100%;
            height: 30px;
            background: #fff;
            transform: skewY(-4deg);
            transform-origin: bottom right;
        }

        .tmpl-body {
            padding: 2.25rem 2rem 1.5rem;
            background: #fff;
        }

        .tmpl-merchant {
            font-weight: 600;
            font-size: 0.95rem;
            color: #334155;
            margin-bottom: 0.5rem;
            text-align: left;
        }

        .tmpl-amount {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            text-align: left;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
        }

        .tmpl-separator {
            border-top: 1px dashed #cbd5e1;
            margin: 0 -2rem 1.5rem;
        }

        .tmpl-qr-box {
            position: relative;
            display: inline-block;
        }

        .tmpl-qr-box img {
            width: 220px;
            height: 220px;
            display: block;
        }

        /* Central Logo in QR */
        .tmpl-qr-logo {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 45px;
            height: 45px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3px;
        }

        .tmpl-qr-logo-inner {
            background: #d91b29;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 900;
        }

        .tmpl-footer-text {
            font-size: 0.8rem;
            color: #94a3b8;
            padding: 0 2rem 1.25rem;
            line-height: 1.5;
        }

        .tmpl-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #64748b;
            padding: 0.4rem 1.2rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 2rem;
        }

        .tmpl-aba-logo {
            font-weight: 800;
            font-size: 2.5rem;
            color: #007c91;
            margin: 2rem 0 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
        }
        .tmpl-aba-logo span { color: #d91b29; }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .status-success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .pulse {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(currentColor, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(currentColor, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(currentColor, 0); }
        }

        .response-viewer {
            grid-column: span 2;
            margin-top: 2rem;
        }

        .json-box {
            background: #020617;
            padding: 1.5rem;
            border-radius: 1rem;
            font-family: 'Fira Code', monospace;
            font-size: 0.8rem;
            overflow-x: auto;
            border: 1px solid rgba(255, 255, 255, 0.05);
            color: #7dd3fc;
            max-height: 400px;
        }

        .loader {
            width: 48px;
            height: 48px;
            border: 5px solid #FFF;
            border-bottom-color: var(--secondary);
            border-radius: 50%;
            display: inline-block;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
        }

        @keyframes rotation {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
    </style>
</head>
<body>

    <h1>ABA Payway Gateway</h1>

    <div class="container">
        <!-- Selection Card -->
        <div class="card">
            <h2>🛒 Select Products</h2>
            <div id="loadingProducts" style="display: none; text-align: center; padding: 2rem;">
                <span class="loader"></span>
            </div>
            <div id="productList" class="product-list">
                <!-- Products dynamically added here -->
            </div>

            <div class="form-group">
                <label>Currency</label>
                <select id="currency">
                    <option value="USD">USD - US Dollar</option>
                    <option value="KHR">KHR - Khmer Riel</option>
                </select>
            </div>

            <div class="form-group">
                <label>Payment Method</label>
                <select id="paymentMethod">
                    <option value="abapay_khqr">KHQR (ABA Pay)</option>
                    <option value="cards">Visa / Mastercard / UPI</option>
                    <option value="abapay,cards">Both (Checkout Page)</option>
                </select>
            </div>

            <div class="form-group" style="margin-top: 2rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                    <span style="color: var(--text-muted)">Total Amount</span>
                    <span id="totalAmountDisplay" style="font-size: 1.5rem; font-weight: 700; color: var(--secondary)">$0.00</span>
                </div>
                <button id="btnGenerate" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    Continue to Payment
                </button>
                <button id="btnAddCard" class="btn btn-secondary" style="margin-top: 1rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                    Add New Card
                </button>
            </div>
        </div>

        <!-- ABA Hidden Elements -->
        <div id="aba_main_modal" class="aba-modal" style="display:none;">
            <div class="aba-modal-content add-card">
                <form method="POST" target="aba_webservice" id="aba_merchant_request" action="">
                    <input type="hidden" name="merchant_id" value="">
                    <input type="hidden" name="req_time" value="">
                    <input type="hidden" name="ctid" value="">
                    <input type="hidden" name="firstname" value="">
                    <input type="hidden" name="lastname" value="">
                    <input type="hidden" name="email" value="">
                    <input type="hidden" name="phone" value="">
                    <input type="hidden" name="return_url" value="">
                    <input type="hidden" name="return_params" value="">
                    <input type="hidden" name="hash" value="">
                </form>
            </div>
        </div>

        <!-- QR Display Card -->
        <div class="card">
            <h2>💳 Payment Status</h2>
            <div class="qr-display" id="qrDisplay">
                <div style="color: var(--text-muted); text-align: center;">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.2; margin-bottom: 1rem;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                    <p>Select products and generate QR to start payment</p>
                </div>
            </div>
            <div id="checkArea" style="display: none; margin-top: 1rem;">
                <button id="btnCheck" class="btn btn-secondary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    Check Transaction Status
                </button>
            </div>
        </div>

        <!-- Response Area -->
        <div class="response-viewer">
            <h2><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg> Raw API Response</h2>
            <pre id="jsonResponse" class="json-box">// API responses will appear here...</pre>
        </div>
    </div>

    <script src="https://checkout-sandbox.payway.com.kh/checkout-popup.html?file=js"></script>
    <script>
        const API_BASE = '/aba-payway';
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let selectedProductIds = [];
        let products = [];
        let currentTranId = null;

        // Add Card Logic
        document.getElementById('btnAddCard').onclick = async () => {
            const btn = document.getElementById('btnAddCard');
            btn.disabled = true;
            btn.innerHTML = '<span class="loader" style="width: 18px; height: 18px; border-width: 2px;"></span> Initializing Card...';

            try {
                const res = await axios.post(`${API_BASE}/add-card`);
                document.getElementById('jsonResponse').textContent = JSON.stringify(res.data, null, 2);
                
                if (res.data.success) {
                    const form = document.getElementById('aba_merchant_request');
                    form.action = res.data.action_url;
                    form.querySelector('[name="merchant_id"]').value = res.data.merchant_id;
                    form.querySelector('[name="req_time"]').value = res.data.req_time;
                    form.querySelector('[name="ctid"]').value = res.data.ctid;
                    form.querySelector('[name="firstname"]').value = res.data.firstname;
                    form.querySelector('[name="lastname"]').value = res.data.lastname;
                    form.querySelector('[name="email"]').value = res.data.email;
                    form.querySelector('[name="phone"]').value = res.data.phone;
                    form.querySelector('[name="return_url"]').value = res.data.return_url;
                    form.querySelector('[name="return_params"]').value = res.data.return_params;
                    form.querySelector('[name="hash"]').value = res.data.hash;

                    // Trigger ABA Popup
                    AbaPayway.addCard();
                }
            } catch (err) {
                console.error(err);
                document.getElementById('jsonResponse').textContent = JSON.stringify(err.response?.data || err.message, null, 2);
                alert('Failed to initialize Add Card');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg> Add New Card';
            }
        };

        // Fetch Products
        async function fetchProducts() {
            document.getElementById('loadingProducts').style.display = 'block';
            try {
                const res = await axios.get(`${API_BASE}/products`);
                products = res.data.products;
                renderProducts();
            } catch (err) {
                console.error(err);
                document.getElementById('jsonResponse').textContent = JSON.stringify(err.response?.data || err.message, null, 2);
            } finally {
                document.getElementById('loadingProducts').style.display = 'none';
            }
        }

        function renderProducts() {
            const list = document.getElementById('productList');
            list.innerHTML = products.map(p => `
                <div class="product-item ${selectedProductIds.includes(p.id) ? 'selected' : ''}" onclick="toggleProduct(${p.id})">
                    <div style="flex: 1">
                        <div style="font-weight: 600">${p.name}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted)">ID: #${p.id}</div>
                    </div>
                    <div style="font-weight: 700; color: var(--secondary)">$${parseFloat(p.price).toFixed(2)}</div>
                </div>
            `).join('');
            updateTotal();
        }

        function toggleProduct(id) {
            const index = selectedProductIds.indexOf(id);
            if (index > -1) {
                selectedProductIds.splice(index, 1);
            } else {
                selectedProductIds.push(id);
            }
            renderProducts();
        }

        function updateTotal() {
            const total = products
                .filter(p => selectedProductIds.includes(p.id))
                .reduce((sum, p) => sum + parseFloat(p.price), 0);
            
            const currency = document.getElementById('currency').value;
            const symbol = currency === 'USD' ? '$' : '៛';
            document.getElementById('totalAmountDisplay').textContent = `${symbol}${total.toLocaleString()}`;
        }

        document.getElementById('currency').addEventListener('change', updateTotal);

        // Generate QR / Checkout
        document.getElementById('btnGenerate').onclick = async () => {
            if (selectedProductIds.length === 0) {
                alert('Please select at least one product');
                return;
            }

            const totalVal = products
                .filter(p => selectedProductIds.includes(p.id))
                .reduce((sum, p) => sum + parseFloat(p.price), 0);
            
            const currency = document.getElementById('currency').value;
            const isKhr = currency === 'KHR';
            const paymentMethod = document.getElementById('paymentMethod').value;
            
            // Basic validation for Payway Sandbox
            if (isKhr && totalVal < 100) { alert('Minimum amount for KHR is 100'); return; }
            if (!isKhr && totalVal < 0.01) { alert('Amount must be greater than 0'); return; }

            const btn = document.getElementById('btnGenerate');
            btn.disabled = true;
            btn.innerHTML = '<span class="loader" style="width: 20px; height: 20px; border-width: 2px;"></span> Processing...';

            currentTranId = 'TEST-' + Date.now();
            
            try {
                // If only KHQR is selected, we can show it directly
                if (paymentMethod === 'abapay_khqr') {
                    const res = await axios.post(`${API_BASE}/generate-qr`, {
                        tran_id: currentTranId,
                        product_ids: selectedProductIds,
                        currency: currency,
                        payment_option: 'abapay'
                    });

                    document.getElementById('jsonResponse').textContent = JSON.stringify(res.data, null, 2);
                    
                    if (res.data.success) {
                        const qrDisplay = document.getElementById('qrDisplay');
                        const symbol = isKhr ? '៛' : '$';
                        const amountStr = isKhr ? totalVal.toLocaleString() : totalVal.toFixed(2);

                        qrDisplay.innerHTML = `
                            <div class="status-badge status-pending">
                                <span class="pulse"></span>
                                Awaiting Payment...
                            </div>
                            
                            <div class="tmpl-aba-logo">ABA<span>'</span> PAY</div>
                            <div class="qr-image-container">
                                <div class="tmpl-header">KHQR</div>
                                <div class="tmpl-body">
                                    <div class="tmpl-merchant">Coffee Khlaing</div>
                                    <div class="tmpl-amount">
                                        <span style="font-size: 1.5rem; font-weight: 500;">${symbol}</span>
                                        <span>${amountStr}</span>
                                    </div>
                                    <div class="tmpl-separator"></div>
                                    <div class="tmpl-qr-box">
                                        <img src="${res.data.qrImage}" alt="ABA QR Code">
                                        <div class="tmpl-qr-logo">
                                            <div class="tmpl-qr-logo-inner">
                                                <svg viewBox="0 0 24 24" fill="white" width="24" height="24"><path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"/></svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tmpl-footer-text">
                                    Scan with ABA Mobile or any KHQR<br>supported banking app
                                </div>
                                <div class="tmpl-badge">
                                    KHQR: ${isKhr ? 'Khmer Riel' : 'US Dollar'}
                                </div>
                            </div>
                            <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 1rem;">Transaction ID: ${currentTranId}</p>
                            <a href="${res.data.abapay_deeplink}" class="btn btn-secondary" style="width: auto; padding: 0.5rem 1.5rem; font-size: 0.9rem;">
                                Open in ABA App
                            </a>
                        `;
                        document.getElementById('checkArea').style.display = 'block';
                    } else {
                        alert('Error: ' + (res.data.response?.description || 'Failed to generate QR'));
                    }
                } else {
                    // For Cards or Both, we use the Checkout redirection
                    const res = await axios.post(`${API_BASE}/checkout`, {
                        tran_id: currentTranId,
                        product_ids: selectedProductIds,
                        currency: currency,
                        payment_option: paymentMethod
                    });

                    document.getElementById('jsonResponse').textContent = JSON.stringify(res.data, null, 2);

                    if (res.data.success) {
                        // Create a hidden form and submit it to ABA
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = res.data.url;
                        
                        for (const [key, value] of Object.entries(res.data.params)) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = key;
                            input.value = value;
                            form.appendChild(input);
                        }
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
            } catch (err) {
                const errorData = err.response?.data;
                document.getElementById('jsonResponse').textContent = JSON.stringify(errorData || err.message, null, 2);
                
                let errorMsg = 'Failed to generate QR';
                if (errorData?.response?.errors) {
                    const errors = errorData.response.errors;
                    errorMsg = Object.values(errors).flat().join('\n');
                } else if (errorData?.response?.description) {
                    errorMsg = errorData.response.description;
                }
                
                alert(errorMsg);
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Generate Checkout QR';
            }
        };

        // Check Status
        document.getElementById('btnCheck').onclick = async () => {
            if (!currentTranId) return;

            const btn = document.getElementById('btnCheck');
            btn.disabled = true;
            btn.innerHTML = '<span class="loader" style="width: 18px; height: 18px; border-width: 2px;"></span> Checking...';

            try {
                const res = await axios.post(`${API_BASE}/check-transaction`, {
                    tran_id: currentTranId
                });

                document.getElementById('jsonResponse').textContent = JSON.stringify(res.data, null, 2);
                
                const status = res.data.response?.status;
                if (status === 0 || status === '0') {
                    document.getElementById('qrDisplay').innerHTML = `
                        <div class="status-badge status-success">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Payment Successful
                        </div>
                        <div style="text-align: center;">
                            <h3 style="font-size: 2rem; margin-bottom: 0.5rem;">🎉</h3>
                            <p style="color: var(--text-muted)">Transaction confirmed!</p>
                        </div>
                    `;
                    document.getElementById('checkArea').style.display = 'none';
                } else if (status === 2 || status === '2') {
                    alert('Payment still pending...');
                } else {
                    alert('Status: ' + (res.data.response?.description || 'Unknown'));
                }
            } catch (err) {
                document.getElementById('jsonResponse').textContent = JSON.stringify(err.response?.data || err.message, null, 2);
                alert('Failed to check status');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Check Transaction Status';
            }
        };

        // Init
        fetchProducts();
    </script>
</body>
</html>
