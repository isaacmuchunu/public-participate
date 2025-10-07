<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $bill->title }} - {{ $clause->clause_number }} - PDF</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .bill-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .clause-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .clause {
            margin-bottom: 30px;
        }
        .clause-number {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .clause-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .clause-content {
            font-size: 14px;
            text-align: justify;
            margin-bottom: 15px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 50px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        @media print {
            body { margin: 0; padding: 15px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="bill-title">{{ $bill->title }}</div>
        <div class="clause-info">Bill Number: {{ $bill->bill_number }}</div>
        <div class="clause-info">Specific Clause: {{ $clause->clause_number }}</div>
        <div class="clause-info">Generated: {{ now()->format('F j, Y \a\t g:i A') }}</div>
    </div>

    <div class="clause">
        <div class="clause-number">Clause {{ $clause->clause_number }}</div>
        @if($clause->title)
        <div class="clause-title">{{ $clause->title }}</div>
        @endif
        <div class="clause-content">
            {!! nl2br(e($clause->content)) !!}
        </div>
    </div>

    <div class="footer">
        <p>This document contains a specific clause from {{ $bill->title }}.</p>
        <p>For official use only. © {{ date('Y') }} Government of Kenya</p>
    </div>
</body>
</html>