<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>You're Offline - Medicine AI</title>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #f8fafc;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      padding: 20px;
      text-align: center;
    }
    .offline-container {
      max-width: 400px;
    }
    .offline-icon {
      font-size: 64px;
      margin-bottom: 20px;
    }
    h1 {
      color: #1e293b;
      font-size: 1.5rem;
      margin-bottom: 12px;
    }
    p {
      color: #64748b;
      line-height: 1.6;
    }
    .offline-card {
      background: white;
      border-radius: 16px;
      padding: 40px 24px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    }
    .status-dot {
      display: inline-block;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: #ef4444;
      margin-right: 8px;
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.4; }
    }
  </style>
</head>
<body>
  <div class="offline-container">
    <div class="offline-card">
      <div class="offline-icon">📡</div>
      <h1><span class="status-dot"></span>You're Offline</h1>
      <p>Please check your internet connection and try again. Your login credentials will work once you're back online.</p>
    </div>
  </div>
</body>
</html>
