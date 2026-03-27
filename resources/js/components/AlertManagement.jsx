import React, { useState, useEffect } from 'react';
import ClinicalMonitoringService from './ClinicalMonitoringService';

const AlertManagement = () => {
    const [alerts, setAlerts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const fetchAlerts = async () => {
            setLoading(true);
            setError(null);
            try {
                const data = await ClinicalMonitoringService.getAlerts();
                setAlerts(data);
            } catch (error) {
                console.error('Failed to fetch alerts:', error);
                setError(error.message || 'Failed to load clinical alerts. Please try again.');
            } finally {
                setLoading(false);
            }
        };

        fetchAlerts();

        const subscription = ClinicalMonitoringService.subscribeToAlerts((newAlert) => {
            setAlerts(prev => [newAlert, ...prev]);
        });

        return () => {
            if (subscription) {
                subscription.stop();
            }
        };
    }, []);

    const handleAcknowledge = async (id) => {
        await ClinicalMonitoringService.acknowledgeAlert(id);
        setAlerts(prev => prev.filter(a => a.id !== id));
    };

    if (loading) {
        return (
            <div className="p-6 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center justify-center h-64">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    <p className="text-gray-500">Loading clinical alerts...</p>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                <div className="flex flex-col items-center justify-center py-12 text-center">
                    <svg className="w-12 h-12 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p className="text-lg font-medium text-red-600 mb-2">Failed to Load Alerts</p>
                    <p className="text-sm text-gray-500 mb-4">{error}</p>
                    <button
                        onClick={() => window.location.reload()}
                        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-sm"
                    >
                        Retry
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
            <div className="flex justify-between items-center mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Clinical Alert Management</h2>
                <span className="px-3 py-1 bg-red-100 text-red-600 rounded-full text-sm font-bold">
                    {alerts.length} Active
                </span>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full text-left">
                    <thead>
                        <tr className="border-b border-gray-100">
                            <th className="pb-3 font-semibold text-gray-600">Patient</th>
                            <th className="pb-3 font-semibold text-gray-600">Severity</th>
                            <th className="pb-3 font-semibold text-gray-600">Message</th>
                            <th className="pb-3 font-semibold text-gray-600">Time</th>
                            <th className="pb-3 font-semibold text-gray-600 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-50">
                        {alerts.length === 0 ? (
                            <tr>
                                <td colSpan="5" className="py-12 text-center">
                                    <div className="flex flex-col items-center text-gray-500">
                                        <svg className="w-12 h-12 text-green-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p className="text-lg font-medium">All clear</p>
                                        <p className="text-sm">No active clinical alerts at this time.</p>
                                    </div>
                                </td>
                            </tr>
                        ) : (
                            alerts.map(alert => (
                                <tr key={alert.id} className="hover:bg-gray-50 transition">
                                    <td className="py-4 font-medium text-gray-900">{alert.patient?.name}</td>
                                    <td className="py-4">
                                        <span className={`px-2 py-1 rounded text-xs font-bold uppercase ${
                                            alert.severity === 'red' ? 'bg-red-100 text-red-700' :
                                            alert.severity === 'orange' ? 'bg-orange-100 text-orange-700' : 'bg-yellow-100 text-yellow-700'
                                        }`}>
                                            {alert.severity}
                                        </span>
                                    </td>
                                    <td className="py-4 text-gray-700">{alert.message}</td>
                                    <td className="py-4 text-sm text-gray-500">
                                        {new Date(alert.triggered_at).toLocaleTimeString()}
                                    </td>
                                    <td className="py-4 text-right">
                                        <button
                                            onClick={() => handleAcknowledge(alert.id)}
                                            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-sm"
                                        >
                                            Acknowledge
                                        </button>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default AlertManagement;
