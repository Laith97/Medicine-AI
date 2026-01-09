import React, { useState, useEffect } from 'react';
import ClinicalMonitoringService from './ClinicalMonitoringService';
import TreatmentOptimization from './TreatmentOptimization';
import { Activity, Zap } from 'lucide-react';

const ClinicalDashboard = ({ patientId, appointmentId }) => {
    const [vitals, setVitals] = useState([]);
    const [scores, setScores] = useState([]);
    const [alerts, setAlerts] = useState([]);
    const [activeTab, setActiveTab] = useState('early-warning');

    useEffect(() => {
        // Initial data fetch could go here
        if (!patientId) {
            console.warn('Patient ID is required for ClinicalDashboard');
            return;
        }

        // Subscribe to real-time updates
        ClinicalMonitoringService.subscribeToPatientData(patientId, (newAlert) => {
            setAlerts(prev => [newAlert, ...prev]);
        });
    }, [patientId]);

    return (
        <div className="space-y-6">
            {/* Tab Navigation */}
            <div className="flex space-x-1 bg-slate-100 p-1 rounded-xl w-fit border border-slate-200">
                <button
                    onClick={() => setActiveTab('early-warning')}
                    className={`flex items-center px-4 py-2 rounded-lg text-sm font-bold transition-all ${
                        activeTab === 'early-warning' 
                        ? 'bg-white text-blue-600 shadow-sm' 
                        : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'
                    }`}
                >
                    <Activity className="w-4 h-4 mr-2" />
                    Early Warning
                </button>
                <button
                    onClick={() => setActiveTab('treatment-optimization')}
                    className={`flex items-center px-4 py-2 rounded-lg text-sm font-bold transition-all ${
                        activeTab === 'treatment-optimization' 
                        ? 'bg-white text-blue-600 shadow-sm' 
                        : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'
                    }`}
                >
                    <Zap className="w-4 h-4 mr-2" />
                    Treatment Optimization
                </button>
            </div>

            {activeTab === 'early-warning' ? (
                <div className="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                    <h2 className="text-2xl font-bold mb-6 text-gray-800">Clinical Early Warning Dashboard</h2>
                    
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        {/* Real-time Vitals Summary */}
                        <div className="p-4 bg-blue-50 rounded-lg border border-blue-100">
                            <h3 className="text-sm font-semibold text-blue-600 uppercase tracking-wider mb-2">Latest NEWS2</h3>
                            <div className="text-3xl font-bold text-blue-900">
                                {scores.find(s => s.type === 'news2')?.score || 'N/A'}
                            </div>
                        </div>
                        
                        <div className="p-4 bg-purple-50 rounded-lg border border-purple-100">
                            <h3 className="text-sm font-semibold text-purple-600 uppercase tracking-wider mb-2">Sepsis Risk</h3>
                            <div className="text-3xl font-bold text-purple-900">
                                {scores.find(s => s.type === 'sepsis')?.risk_level || 'Low'}
                            </div>
                        </div>

                        <div className="p-4 bg-red-50 rounded-lg border border-red-100">
                            <h3 className="text-sm font-semibold text-red-600 uppercase tracking-wider mb-2">Active Alerts</h3>
                            <div className="text-3xl font-bold text-red-900">
                                {alerts.length}
                            </div>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold text-gray-700">Recent Alerts</h3>
                        {alerts.length === 0 ? (
                            <p className="text-gray-500 italic">No active alerts for this patient.</p>
                        ) : (
                            alerts.map(alert => (
                                <div key={alert.id} className={`p-4 rounded-lg border-l-4 ${
                                    alert.severity === 'red' ? 'bg-red-50 border-red-500' : 
                                    alert.severity === 'orange' ? 'bg-orange-50 border-orange-500' : 'bg-yellow-50 border-yellow-500'
                                }`}>
                                    <div className="flex justify-between items-start">
                                        <div>
                                            <p className="font-bold text-gray-900">{alert.message}</p>
                                            <p className="text-sm text-gray-600">{new Date(alert.triggered_at).toLocaleString()}</p>
                                        </div>
                                        <button 
                                            onClick={() => ClinicalMonitoringService.acknowledgeAlert(alert.id)}
                                            className="px-3 py-1 bg-white border border-gray-200 rounded text-sm hover:bg-gray-50 transition"
                                        >
                                            Acknowledge
                                        </button>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            ) : (
                <TreatmentOptimization patientId={patientId} appointmentId={appointmentId} />
            )}
        </div>
    );
};

export default ClinicalDashboard;
