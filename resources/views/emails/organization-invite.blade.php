<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Invitation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #0f0f1a !important; color: #e2e8f0 !important; }
        .wrapper { max-width: 560px; margin: 40px auto; padding: 20px; }
        .card { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%) !important; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important; padding: 32px 40px; text-align: center; }
        .logo { font-size: 24px; font-weight: 800; color: #166534 !important; letter-spacing: -0.5px; }
        .logo span { color: #16a34a !important; }
        .body { padding: 40px; }
        .title { font-size: 22px; font-weight: 700; color: #f1f5f9 !important; margin-bottom: 12px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.07); margin-bottom: 24px; }
        .subtitle { font-size: 15px; color: #94a3b8 !important; line-height: 1.6; margin-bottom: 32px; }
        .org-badge { display: inline-block; background: rgba(99, 102, 241, 0.2) !important; border: 1px solid rgba(99, 102, 241, 0.4); border-radius: 8px; padding: 8px 16px; font-size: 16px; font-weight: 600; color: #a5b4fc !important; margin-bottom: 32px; }
        .buttons { display: flex; gap: 12px; margin-bottom: 32px; }
        .btn { display: inline-block; padding: 14px 28px; border-radius: 10px; font-size: 15px; font-weight: 600; text-decoration: none; text-align: center; flex: 1; }
        .btn-accept { background: linear-gradient(135deg, #6366f1, #8b5cf6) !important; color: #ffffff !important; }
        .btn-decline { background: rgba(255,255,255,0.05) !important; border: 1px solid rgba(255,255,255,0.15); color: #94a3b8 !important; }
        .expiry { font-size: 13px; color: #64748b !important; text-align: center; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.07); }
        .footer { padding: 20px 40px; text-align: center; font-size: 12px; color: #475569 !important; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="header">
            <div class="logo">Git<span>Code</span>Gud</div>
        </div>
        <div class="body">
            <p class="title">You've been invited!</p>
            <p class="subtitle">You have been invited to join the following organization on GitCodeGud:</p>

            <div class="org-badge">{{ $organization->name }}</div>

            <div class="buttons">
                <a href="{{ $acceptUrl }}" class="btn btn-accept">Accept Invitation</a>
                <a href="{{ $declineUrl }}" class="btn btn-decline">Decline</a>
            </div>

            <p class="expiry">This invitation expires in 7 days.</p>
        </div>
        <div class="footer">
            GitCodeGud · Gamified Open Source Bounty Platform<br>
            If you did not expect this invitation, you can safely ignore this email.
        </div>
    </div>
</div>
</body>
</html>
