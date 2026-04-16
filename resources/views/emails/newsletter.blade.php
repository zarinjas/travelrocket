<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 32px 16px; }
        .card { background: #ffffff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .greeting { font-size: 15px; color: #374151; margin-bottom: 20px; }
        .content { font-size: 14px; color: #4b5563; line-height: 1.7; }
        .content img { max-width: 100%; height: auto; border-radius: 8px; }
        .footer { text-align: center; padding: 24px 0 0; font-size: 11px; color: #9ca3af; }
        .footer a { color: #6b7280; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <p class="greeting">Hi {{ $recipientName }},</p>
        <div class="content">
            {!! $bodyHtml !!}
        </div>
    </div>
    <div class="footer">
        <p>Sent by {{ $tenantName }} via TravelRocket</p>
    </div>
</div>
</body>
</html>
