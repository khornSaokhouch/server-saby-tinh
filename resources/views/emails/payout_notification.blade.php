<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payout Notification</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f1f5f9; padding: 32px 16px; }
    .wrapper { max-width: 560px; margin: 0 auto; }
    .card { background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
    .header { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 32px 32px 24px; text-align: center; }
    .icon { width: 56px; height: 56px; background: rgba(255,255,255,0.15); border-radius: 16px; margin: 0 auto 16px; display: block; text-align: center; line-height: 56px; font-size: 28px; }
    .header h1 { color: #ffffff; font-size: 20px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 4px; }
    .header p { color: rgba(255,255,255,0.75); font-size: 12px; font-weight: 500; }
    .body { padding: 28px 32px; }
    .greeting { font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 10px; }
    .message { font-size: 12px; color: #64748b; line-height: 1.7; margin-bottom: 24px; }
    /* Summary badge */
    .summary-badge { display: inline-flex; align-items: center; gap: 6px; background: #eef2ff; color: #4f46e5; border-radius: 20px; padding: 5px 14px; font-size: 11px; font-weight: 800; letter-spacing: 0.05em; margin-bottom: 20px; }
    /* Payout items table */
    .payout-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .payout-table thead tr { background: #f8fafc; }
    .payout-table thead th { padding: 8px 12px; text-align: left; font-size: 9px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; }
    .payout-table thead th:last-child { text-align: right; }
    .payout-table tbody tr { border-bottom: 1px solid #f1f5f9; }
    .payout-table tbody tr:last-child { border-bottom: none; }
    .payout-table tbody td { padding: 10px 12px; font-size: 12px; color: #334155; font-weight: 600; vertical-align: middle; }
    .payout-table tbody td:last-child { text-align: right; font-weight: 800; color: #4f46e5; }
    .mono { font-family: 'Courier New', monospace; font-size: 10px; color: #94a3b8; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; }
    .badge-success { background: #d1fae5; color: #059669; }
    .badge-pending { background: #fef3c7; color: #d97706; }
    /* Total row */
    .total-row { background: #f8fafc; border-radius: 12px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .total-label { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; }
    .total-value { font-size: 18px; font-weight: 900; color: #4f46e5; }
    .highlight { background: #f8fafc; border-radius: 12px; padding: 14px 16px; margin-bottom: 24px; border: 1px solid #e2e8f0; font-size: 12px; color: #475569; line-height: 1.6; }
    .footer { text-align: center; font-size: 10px; color: #cbd5e1; padding: 20px 32px; font-weight: 600; line-height: 1.6; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="card">
      <!-- Header -->
      <div class="header">
        <div class="icon">💰</div>
        <h1>
          @if($payouts->count() === 1)
            Payout Processed
          @else
            {{ $payouts->count() }} Payouts Processed
          @endif
        </h1>
        <p>{{ config('app.name') }} Finance System</p>
      </div>

      <!-- Body -->
      <div class="body">
        <p class="greeting">Hello, {{ $storeName }}!</p>
        <p class="message">
          @if($payouts->count() === 1)
            A payout has been processed for your store. Please review the details below.
          @else
            {{ $payouts->count() }} payouts have been processed for your store in a single batch. Please review all details below.
          @endif
        </p>

        <!-- Summary badge -->
        <div>
          <span class="summary-badge">
            💳 &nbsp;{{ $payouts->count() }} {{ $payouts->count() === 1 ? 'Payout' : 'Payouts' }} &nbsp;·&nbsp; {{ $payouts->first()?->currency }}
          </span>
        </div>

        <!-- Payout rows -->
        <table class="payout-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Invoice</th>
              <th>Reference</th>
              <th>Date</th>
              <th>Status</th>
              <th>Amount</th>
            </tr>
          </thead>
          <tbody>
            @foreach($payouts as $i => $payout)
            <tr>
              <td style="color:#94a3b8; font-size:10px;">{{ $i + 1 }}</td>
              <td>{{ $payout->invoice?->invoice_number ?? 'INV-' . $payout->invoice_id }}</td>
              <td><span class="mono">{{ $payout->transaction_reference ?? '—' }}</span></td>
              <td style="font-size:11px; color:#64748b;">
                {{ $payout->paid_at ? \Carbon\Carbon::parse($payout->paid_at)->format('d M Y') : '—' }}
              </td>
              <td>
                @php $s = $payout->status?->status; @endphp
                @if($s === 'Paid' || $s === 'Success')
                  <span class="badge badge-success">{{ $s }}</span>
                @else
                  <span class="badge badge-pending">{{ $s ?? 'Pending' }}</span>
                @endif
              </td>
              <td>{{ number_format($payout->amount, 2) }} {{ $payout->currency }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>

        <!-- Grand Total -->
        @php $totalAmount = $payouts->sum('amount'); $currency = $payouts->first()?->currency; @endphp
        <div class="total-row">
          <span class="total-label">Grand Total ({{ $payouts->count() }} {{ $payouts->count() === 1 ? 'payout' : 'payouts' }})</span>
          <span class="total-value">{{ number_format($totalAmount, 2) }} {{ $currency }}</span>
        </div>

        <div class="highlight">
          📌 <strong>Note:</strong> If you have any questions, please contact our support team with your transaction reference number.
        </div>
      </div>

      <!-- Footer -->
      <div class="footer">
        © {{ date('Y') }} {{ config('app.name') }} · All rights reserved.<br>
        This is an automated message, please do not reply directly.
      </div>
    </div>
  </div>
</body>
</html>
