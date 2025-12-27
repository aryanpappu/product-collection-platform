<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import {{ $isSuccess ? 'Completed' : 'Failed' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: {{ $isSuccess ? '#10b981' : '#ef4444' }};
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }

        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }

        .stats {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .stat-row:last-child {
            border-bottom: none;
        }

        .stat-label {
            font-weight: 600;
            color: #6b7280;
        }

        .stat-value {
            font-weight: 700;
            color: #111827;
        }

        .success {
            color: #10b981;
        }

        .failed {
            color: #ef4444;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>{{ $isSuccess ? '✓' : '✗' }} Product Import {{ $isSuccess ? 'Completed' : 'Failed' }}</h1>
</div>

<div class="content">
    <p>Hello {{ $merchantName }},</p>

    @if($isSuccess)
        <p>Your product import has been completed successfully!</p>
    @else
        <p>Unfortunately, your product import has failed. Please review the error details below.</p>
    @endif

    <div class="stats">
        <div class="stat-row">
            <span class="stat-label">File Name:</span>
            <span class="stat-value">{{ $importJob->filename }}</span>
        </div>

        <div class="stat-row">
            <span class="stat-label">Status:</span>
            <span class="stat-value {{ $isSuccess ? 'success' : 'failed' }}">
                    {{ ucfirst($importJob->status) }}
                </span>
        </div>

        <div class="stat-row">
            <span class="stat-label">Total Rows:</span>
            <span class="stat-value">{{ number_format($importJob->total_rows) }}</span>
        </div>

        @if($isSuccess)
            <div class="stat-row">
                <span class="stat-label">Successful:</span>
                <span class="stat-value success">{{ number_format($importJob->successful_rows) }}</span>
            </div>

            @if($importJob->failed_rows > 0)
                <div class="stat-row">
                    <span class="stat-label">Failed:</span>
                    <span class="stat-value failed">{{ number_format($importJob->failed_rows) }}</span>
                </div>
            @endif
        @endif

        @if($importJob->error_message)
            <div class="stat-row">
                <span class="stat-label">Error:</span>
                <span class="stat-value failed">{{ $importJob->error_message }}</span>
            </div>
        @endif

        <div class="stat-row">
            <span class="stat-label">Started:</span>
            <span class="stat-value">{{ $importJob->started_at?->format('M d, Y H:i:s') ?? 'N/A' }}</span>
        </div>

        <div class="stat-row">
            <span class="stat-label">Completed:</span>
            <span class="stat-value">{{ $importJob->completed_at?->format('M d, Y H:i:s') ?? 'N/A' }}</span>
        </div>

        @if($importJob->started_at && $importJob->completed_at)
            <div class="stat-row">
                <span class="stat-label">Duration:</span>
                <span class="stat-value">
                        {{ $importJob->started_at->diffForHumans($importJob->completed_at, true) }}
                    </span>
            </div>
        @endif
    </div>

    @if($isSuccess && $importJob->failed_rows > 0)
        <p style="color: #d97706; background-color: #fef3c7; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b;">
            <strong>Note:</strong> Some rows failed to import. Please check the import logs for details.
        </p>
    @endif

    @if(!$isSuccess)
        <p style="color: #dc2626; background-color: #fee2e2; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444;">
            <strong>Action Required:</strong> Please review the error message above and try importing again with a
            corrected file.
        </p>
    @endif

    <p>If you have any questions or need assistance, please contact our support team.</p>

    <p>Thank you for using our platform!</p>
</div>

<div class="footer">
    <p>This is an automated email. Please do not reply to this message.</p>
    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
</div>
</body>
</html>
