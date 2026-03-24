import ClinicalMonitoringService from './ClinicalMonitoringService';
import TreatmentOptimization from './TreatmentOptimization';
import { Activity, Zap, Brain, TrendingUp, AlertTriangle, CheckCircle } from 'lucide-react';
import { Line } from 'react-chartjs-2';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
);

const ClinicalDashboard = ({ patientId, appointmentId }) => {
    const [vitals, setVitals] = useState([]);
    const [scores, setScores] = useState([]);
    const [alerts, setAlerts] = useState([]);
    const [activeTab, setActiveTab] = useState('early-warning');
    const [trends, setTrends] = useState({ labels: [], datasets: [] });
    const [aiInsights, setAiInsights] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!patientId) {
            // console.warn('Patient ID is required for ClinicalDashboard');
            return;
        }

        let isMounted = true;
        const fetchData = async () => {
            setLoading(true);
            try {
                // Fetch historical scores for trends
                const historicalData = await ClinicalMonitoringService.getHistoricalScores(patientId);
                if (!isMounted) return;

                if (historicalData && historicalData.length > 0) {
                    const labels = historicalData.map(d => new Date(d.calculated_at).toLocaleTimeString());
                    const news2Data = historicalData.filter(d => d.algorithm_type === 'news2').map(d => d.score);
                    
                    setTrends({
                        labels,
                        datasets: [
                            {
                                label: 'NEWS2 Score',
                                data: news2Data,
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                                tension: 0.3
                            }
                        ]
                    });
                }

                // Fetch latest AI insights
                const insights = await ClinicalMonitoringService.getLatestInsights(patientId);
                if (!isMounted) return;
                setAiInsights(insights);

            } catch (error) {
                // console.error('Error fetching clinical data:', error);
            } finally {
                if (isMounted) setLoading(false);
            }
        };

        fetchData();

        // Subscribe to real-time updates
        const unsubscribe = ClinicalMonitoringService.subscribeToPatientData(patientId, (newAlert) => {
            if (!isMounted) return;
            setAlerts(prev => [newAlert, ...prev]);
            // Refresh insights and trends when new alert arrives
            fetchData();
        });

        return () => {
            isMounted = false;
            if (unsubscribe && typeof unsubscribe === 'function') {
                unsubscribe();
            }
        };
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

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                        {/* Trend Chart */}
                        <div className="lg:col-span-2 p-6 bg-white rounded-xl border border-gray-100 shadow-sm">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="text-lg font-semibold text-gray-700 flex items-center">
                                    <TrendingUp className="w-5 h-5 mr-2 text-blue-500" />
                                    Risk Trajectory (NEWS2)
                                </h3>
                                {aiInsights?.risk_summary?.trend && (
                                    <span className={`px-2 py-1 rounded text-xs font-bold ${
                                        aiInsights.risk_summary.trend.trend_direction === 'rising' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'
                                    }`}>
                                        {aiInsights.risk_summary.trend.trend_direction.toUpperCase()}
                                    </span>
                                )}
                            </div>
                            <div className="h-64">
                                {trends.labels.length > 0 ? (
                                    <Line 
                                        data={trends} 
                                        options={{
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            scales: {
                                                y: { beginAtZero: true, max: 20 }
                                            }
                                        }} 
                                    />
                                ) : (
                                    <div className="flex items-center justify-center h-full text-gray-400 italic">
                                        Insufficient data for trend analysis
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* AI Insights Panel */}
                        <div className="p-6 bg-slate-50 rounded-xl border border-slate-200 shadow-sm">
                            <h3 className="text-lg font-semibold text-slate-800 flex items-center mb-4">
                                <Brain className="w-5 h-5 mr-2 text-purple-600" />
                                Clinical AI Insights
                            </h3>
                            {aiInsights ? (
                                <div className="space-y-4">
                                    <p className="text-sm text-slate-700 leading-relaxed">
                                        {aiInsights.narrative}
                                    </p>
                                    <div className="space-y-2">
                                        <h4 className="text-xs font-bold text-slate-500 uppercase tracking-wider">Suggested Actions</h4>
                                        {aiInsights.suggested_actions.map((action, idx) => (
                                            <div key={idx} className="flex items-start text-sm text-slate-800 bg-white p-2 rounded border border-slate-100">
                                                <CheckCircle className="w-4 h-4 mr-2 text-green-500 mt-0.5 flex-shrink-0" />
                                                {action}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ) : (
                                <div className="flex flex-col items-center justify-center h-48 text-slate-400">
                                    <Zap className="w-8 h-8 mb-2 opacity-20" />
                                    <p className="text-sm italic">Analyzing clinical data...</p>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div className="flex justify-between items-center">
                            <h3 className="text-lg font-semibold text-gray-700">Recent Alerts</h3>
                            <span className="text-xs text-gray-400">Real-time updates active</span>
                        </div>
                        {alerts.length === 0 ? (
                            <div className="p-8 text-center bg-gray-50 rounded-xl border border-dashed border-gray-200">
                                <p className="text-gray-500 italic">No active alerts for this patient.</p>
                            </div>
                        ) : (
                            <div className="grid gap-4">
                                {alerts.map(alert => (
                                    <div key={alert.id} className={`p-4 rounded-lg border-l-4 shadow-sm transition-all hover:shadow-md ${
                                        alert.severity === 'red' || alert.severity === 'high' ? 'bg-red-50 border-red-500' : 
                                        alert.severity === 'orange' || alert.severity === 'medium' ? 'bg-orange-50 border-orange-500' : 'bg-yellow-50 border-yellow-500'
                                    }`}>
                                        <div className="flex justify-between items-start">
                                            <div className="flex items-start">
                                                <AlertTriangle className={`w-5 h-5 mr-3 mt-0.5 ${
                                                    alert.severity === 'red' || alert.severity === 'high' ? 'text-red-500' : 'text-orange-500'
                                                }`} />
                                                <div>
                                                    <p className="font-bold text-gray-900">{alert.message}</p>
                                                    <p className="text-xs text-gray-500 mt-1">
                                                        {new Date(alert.triggered_at).toLocaleString()} • {alert.status.toUpperCase()}
                                                    </p>
                                                </div>
                                            </div>
                                            <button 
                                                onClick={() => ClinicalMonitoringService.acknowledgeAlert(alert.id)}
                                                className="px-4 py-1.5 bg-white border border-gray-200 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition shadow-sm"
                                            >
                                                Acknowledge
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
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
