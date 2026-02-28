import React, { useState, useEffect } from 'react';
import ClinicalMonitoringService from './ClinicalMonitoringService';

const AlertManagement = () => {
    const [alerts, setAlerts] = useState([]);

    useEffect(() => {
        const fetchAlerts = async () => {
            const data = await ClinicalMonitoringService.getAlerts();
            setAlerts(data);
        };

        fetchAlerts();

        ClinicalMonitoringService.subscribeToAlerts((newAlert) => {
            setAlerts(prev => [newAlert, ...prev]);
        });
    }, []);

    const handleAcknowledge = async (id) => {
        await ClinicalMonitoringService.acknowledgeAlert(id);
        setAlerts(prev => prev.filter(a => a.id !== id));
    };

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
                                <td colSpan="5" className="py-8 text-center text-gray-500 italic">
                                    All clear. No active clinical alerts.
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
