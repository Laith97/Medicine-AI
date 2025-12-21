import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom/client';

const RealtimeVitalsChart = () => {
    const [vitals, setVitals] = useState([]);

    useEffect(() => {
        // In a real application, you would use a WebSocket to receive real-time data.
        // For this example, we will simulate data updates every 2 seconds.
        const interval = setInterval(() => {
            const newVital = {
                name: 'Heart Rate',
                value: Math.floor(Math.random() * (120 - 60 + 1)) + 60,
                timestamp: new Date().toLocaleTimeString(),
            };
            setVitals(prevVitals => [...prevVitals, newVital]);
        }, 2000);

        return () => clearInterval(interval);
    }, []);

    return (
        <div>
            <h1>Real-time Vitals</h1>
            <ul>
                {vitals.map((vital, index) => (
                    <li key={index}>
                        {vital.timestamp}: {vital.name} - {vital.value}
                    </li>
                ))}
            </ul>
        </div>
    );
};


const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(
  <React.StrictMode>
    <RealtimeVitalsChart />
  </React.StrictMode>
);
