<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Processing...</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    html, body { height: 100%; }
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #f8f9fa, #eef2f7);
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }
    .card {
      width: min(520px, 92vw);
      border: 1px solid #e9ecef;
      box-shadow: 0 10px 25px rgba(0,0,0,0.08);
      border-radius: 14px;
      animation: pulse 3s infinite;
    }
    @keyframes pulse {
      0% { box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
      50% { box-shadow: 0 15px 35px rgba(0,0,0,0.12); }
      100% { box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
    }
    .progress {
      height: 16px;
      background-color: #eef1f4;
      border-radius: 10px;
    }
    .progress-bar {
      background: linear-gradient(90deg, #DE6262, #ff8a65);
    }
    .subtext {
      color: #6c757d;
      font-size: 0.92rem;
    }
    .tips {
      font-size: 0.85rem;
      color: #6c757d;
      list-style: none;
      padding-left: 0;
    }
  </style>
</head>
<body>
  <div class="card p-4 p-md-5 bg-white">
    <div class="text-center mb-3">
      <div class="spinner-border text-danger mb-3" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <div class="mb-2" style="font-size: 1.15rem; font-weight: 600; color:#2c3e50;">
        Processing your request
      </div>
      <div class="subtext">This may take a moment. Keep this page open until results appear in the main tab.</div>
    </div>

    <div class="progress mb-3">
      <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
           role="progressbar" style="width: 0%"></div>
    </div>

    <ul id="tips" class="tips text-center mb-0"></ul>

    <div class="text-center mt-3" style="font-size: 0.85rem; color:#6c757d;">
      You can close this page after you see the result.
    </div>
  </div>

  <script>
    // Rotating tips
    const tips = [
      "💡 Do not close this page while processing.",
      "⚡ The AI analysis will appear in your main window.",
      "✅ You can safely close this page once results appear.",
      "⏳ Processing may take longer for larger requests."
    ];
    let i = 0;
    const tipsContainer = document.getElementById("tips");
    function showTip() {
      tipsContainer.innerHTML = `<li>${tips[i]}</li>`;
      i = (i + 1) % tips.length;
    }
    showTip();
    setInterval(showTip, 4000);

    // Fake progress simulation
    let width = 0;
    const bar = document.getElementById("progressBar");
    const interval = setInterval(() => {
      if (width >= 100) {
        clearInterval(interval);
      } else {
        width += Math.random() * 5; // simulate progress
        if (width > 100) width = 100;
        bar.style.width = width + "%";
      }
    }, 500);
  </script>
</body>
</html>
