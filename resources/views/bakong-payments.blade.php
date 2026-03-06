<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakong Checkout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .bakong-red { background-color: #E61B23; }
        .text-bakong { color: #E61B23; }
    </style>
</head>
<body class="py-10 px-4">
<div class="max-w-5xl mx-auto">

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-xl">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Left: Checkout Form -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-200">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-2 h-8 bakong-red rounded-full"></div>
                    <h2 class="text-2xl font-bold text-gray-800">Checkout</h2>
                </div>

                <form method="POST" action="{{ route('bakong.generate-qr') }}" class="space-y-6">
                    @csrf

                    <!-- Product -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Select Item</label>
                        <select name="product_id" class="w-full p-4 bg-gray-50 border rounded-2xl outline-none focus:ring-2 focus:ring-red-500">
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} — {{ number_format($p->price) }} KHR
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Currency -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Currency</label>
                        <div class="flex gap-3">
                            <label class="flex-1">
                                <input type="radio" name="currency" value="KHR" class="hidden peer" {{ old('currency', 'KHR') === 'KHR' ? 'checked' : '' }}>
                                <div class="text-center p-3 border rounded-xl cursor-pointer peer-checked:bg-red-50 peer-checked:border-red-500 peer-checked:text-red-600">KHR</div>
                            </label>
                            <label class="flex-1">
                                <input type="radio" name="currency" value="USD" class="hidden peer" {{ old('currency') === 'USD' ? 'checked' : '' }}>
                                <div class="text-center p-3 border rounded-xl cursor-pointer peer-checked:bg-red-50 peer-checked:border-red-500 peer-checked:text-red-600">USD</div>
                            </label>
                        </div>
                    </div>

                    <!-- Payment Account -->
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Select Payment Account</label>
                        <select name="payment_account_id" class="w-full p-4 bg-gray-50 border rounded-2xl outline-none focus:ring-2 focus:ring-red-500">
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" {{ old('payment_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->account_name }} ({{ $account->currency }}) — {{ $account->account_city }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="w-full bakong-red text-white font-bold py-4 rounded-2xl shadow-lg hover:opacity-90 transition-all">
                        Generate KHQR
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: QR Display -->
        <div class="lg:col-span-7 flex justify-center">
            @isset($qrImage)
            <div class="bg-white p-8 rounded-[2.5rem] shadow-2xl border-4 border-white w-full max-w-md text-center">
                <div class="flex flex-col items-center mb-6">
                    <img src="{{ $sourceInfo['appIconUrl'] ?? 'https://bakong.nbc.org.kh/images/logo.svg' }}" class="h-12 mb-2">
                    <span class="text-[10px] font-bold tracking-[0.2em] text-gray-400 uppercase">Scan with {{ $sourceInfo['appName'] ?? 'Bakong' }}</span>
                </div>

                <div class="bg-gray-50 p-6 rounded-3xl inline-block mb-6 shadow-inner border border-gray-100">
                    {!! $qrImage !!}
                </div>

                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-500">{{ $product->name }}</h3>
                    <p class="text-4xl font-black text-gray-900 mt-1">
                        {{ $currency === 'USD' ? '$' . number_format($displayAmount, 2) : number_format($displayAmount) . ' ៛' }}
                    </p>
                </div>

                <form method="POST" action="{{ route('bakong.check-md5') }}">
                    @csrf
                    <input type="hidden" name="md5" value="{{ $md5 }}">
                    <button type="submit" class="w-full bg-gray-900 text-white py-4 rounded-2xl font-bold hover:bg-black transition-all flex items-center justify-center gap-2">
                        Verify Payment
                    </button>
                </form>
            </div>
            @else
            <div class="flex flex-col items-center justify-center h-full text-gray-300 opacity-50">
                <p class="font-medium">Please select a product to begin</p>
            </div>
            @endisset
        </div>

    </div>

    <!-- Transaction Result -->
    @if(session('checkResult'))
        @php $res = session('checkResult'); $data = $res['raw_response'] ?? []; @endphp
        <div class="mt-12 bg-white rounded-[2rem] shadow-xl border overflow-hidden">
            <div class="bg-gray-900 p-6 flex justify-between items-center">
                <div>
                    <h2 class="text-white text-xl font-bold">Transaction Report</h2>
                    <p class="text-gray-400 text-xs">Status: {{ strtoupper($res['status']) }}</p>
                </div>
                <button onclick="window.print()" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-lg text-xs font-bold transition-all">Print Log</button>
            </div>

            <!-- Highlights for User -->
            @if($res['status'] === 'success' && !empty($data))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6 bg-gray-50 border-b">
                <div class="bg-white p-4 rounded-xl border border-gray-100">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Tracking Status</p>
                    <p class="text-base font-bold text-green-600">{{ $data['trackingStatus'] ?? 'COMPLETED' }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-100">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Receiver Bank</p>
                    <p class="text-base font-bold text-gray-800">{{ $data['receiverBank'] ?? 'National Bank of Cambodia (Bakong)' }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-100">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Receiver Account</p>
                    <p class="text-base font-bold text-gray-800">{{ $data['receiverBankAccount'] ?? ($data['toAccountId'] ?? 'N/A') }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-100">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Instruction Ref</p>
                    <p class="text-xs font-mono text-blue-600 break-all">{{ $data['instructionRef'] ?? ($data['hash'] ?? 'N/A') }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-100 md:col-span-2">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Description</p>
                    <p class="text-sm text-gray-600">{{ $data['description'] ?? 'Bakong QR Payment' }}</p>
                </div>
            </div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-400 uppercase text-[10px] font-black tracking-widest border-b">
                            <th class="px-8 py-4">Data Property</th>
                            <th class="px-8 py-4">Value / Info</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @if(isset($res['raw_response']))
                            @foreach($res['raw_response'] as $key => $val)
                                @if(!is_array($val))
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-8 py-4 font-bold text-gray-500 w-1/4">{{ $key }}</td>
                                    <td class="px-8 py-4 font-mono text-xs text-blue-600">{{ $val }}</td>
                                </tr>
                                @endif
                            @endforeach

                            @if(isset($res['raw_response']['data']))
                                @foreach($res['raw_response']['data'] as $k => $v)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="px-8 py-4 font-bold text-gray-800">{{ $k }}</td>
                                        <td class="px-8 py-4 break-all">
                                            @if(str_contains(strtolower($k), 'ms'))
                                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded font-bold text-xs">
                                                    {{ \Carbon\Carbon::createFromTimestampMs($v)->toDayDateTimeString() }}
                                                </span>
                                            @else
                                                <span class="text-gray-600 font-mono text-xs">{{ is_array($v) ? json_encode($v) : $v }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        @else
                            <tr>
                                <td colspan="2" class="px-8 py-10 text-center text-red-500 font-bold">
                                    {{ $res['message'] ?? 'Unable to retrieve transaction details.' }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
</body>
</html>