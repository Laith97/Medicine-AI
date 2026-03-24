<!DOCTYPE html>
<html>
<head>
    <title>WebSocket Test</title>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
</head>
<body>
    <h1>WebSocket Test Page</h1>
    
    <div id="messages">
        <h3>Messages will appear here:</h3>
        <ul id="message-list"></ul>
    </div>
    
    <div>
        <input type="text" id="message-input" placeholder="Enter message to broadcast">
        <button onclick="sendMessage()">Send Message</button>
    </div>
    
    <script>
        // Enable pusher logging - don't include this in production
        Pusher.logToConsole = true;

        var pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
            cluster: '{{ env("PUSHER_APP_CLUSTER") }}',
            wsHost: window.location.hostname,
            wsPort: 8080,
            wssPort: 8080,
            forceTLS: false,
            scheme: 'http'
        });

        var channel = pusher.subscribe('notifications');
        channel.bind('App.Events.NewNotification', function(data) {
            var messageList = document.getElementById('message-list');
            var listItem = document.createElement('li');
            listItem.textContent = 'Received: ' + data.message;
            messageList.appendChild(listItem);
        });
        
        function sendMessage() {
            const message = document.getElementById('message-input').value;
            if (message) {
                // Send the message to the server
                fetch('/send-notification', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ message: message })
                })
                .then(response => response.json())
                .then(data => {
                    // console.log('Message sent:', data);
                    document.getElementById('message-input').value = '';
                })
                .catch(error => // console.error('Error:', error));
            }
        }
    </script>
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
</body>
</html>