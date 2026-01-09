import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { 
  Activity, 
  CheckCircle, 
  XCircle, 
  AlertTriangle, 
  TrendingUp, 
  Shield, 
  DollarSign, 
  Clock, 
  ChevronRight,
  Info,
  RefreshCw
} from 'lucide-react';

const TreatmentOptimization = ({ patientId, appointmentId }) => {
  const [recommendation, setRecommendation] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [actionLoading, setActionLoading] = useState(false);

  useEffect(() => {
    fetchRecommendations();
  }, [patientId, appointmentId]);

  const fetchRecommendations = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await axios.get(`/api/treatment-optimization/${patientId}/${appointmentId}`);
      
      if (response.data && response.data.length > 0) {
        setRecommendation(response.data[0]); // Get the latest recommendation
      } else {
        // If no recommendation exists, trigger a new one
        await handleReanalyze();
      }
    } catch (err) {
      console.error('Error fetching recommendations:', err);
      setError('Failed to load treatment recommendations. Please try re-analyzing.');
    } finally {
      setLoading(false);
    }
  };

  const handleReanalyze = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await axios.post('/api/treatment-optimization/generate', {
        patient_id: patientId,
        appointment_id: appointmentId,
        conditions: ['Hypertension', 'Type 2 Diabetes'], // This should ideally come from props or another API
        demographics: { age: 45, gender: 'Male', weight: 85, height: 180 } // Mock demographics for now
      });
      setRecommendation(response.data);
    } catch (err) {
      console.error('Error generating recommendations:', err);
      setError('Failed to generate AI recommendations.');
    } finally {
      setLoading(false);
    }
  };

  const handleValidate = async () => {
    if (!recommendation) return;
    setActionLoading(true);
    try {
      await axios.post(`/api/treatment-optimization/${recommendation.id}/validate`);
      alert('Treatment plan validated and implemented successfully.');
      fetchRecommendations(); // Refresh data
    } catch (err) {
      console.error('Error validating recommendation:', err);
      alert('Failed to validate treatment plan.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleReject = async () => {
    if (!recommendation) return;
    if (!confirm('Are you sure you want to reject this treatment plan?')) return;
    
    setActionLoading(true);
    try {
      await axios.post(`/api/treatment-optimization/${recommendation.id}/reject`);
      alert('Treatment plan rejected.');
      fetchRecommendations(); // Refresh data
    } catch (err) {
      console.error('Error rejecting recommendation:', err);
      alert('Failed to reject treatment plan.');
    } finally {
      setActionLoading(false);
    }
  };

  const ScoreCard = ({ title, score, icon: Icon, color }) => (
    <div className="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-4 flex items-center space-x-4">
      <div className={`p-3 rounded-lg ${color}`}>
        <Icon className="w-6 h-6 text-white" />
      </div>
      <div>
        <p className="text-sm text-gray-400">{title}</p>
        <p className="text-xl font-bold text-white">{(score * 100).toFixed(0)}%</p>
      </div>
    </div>
  );

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center p-12 space-y-4 bg-slate-900 rounded-2xl border border-slate-800">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        <p className="text-slate-400 font-medium">Analyzing patient data and optimizing treatment...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6 bg-red-900/20 border border-red-500/50 rounded-2xl flex items-center space-x-4 text-red-400">
        <AlertTriangle className="w-8 h-8" />
        <p>{error}</p>
      </div>
    );
  }

  return (
    <div className="bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden shadow-2xl">
      {/* Header */}
      <div className="p-6 border-b border-slate-800 bg-gradient-to-r from-blue-600/20 to-purple-600/20 flex justify-between items-center">
        <div>
          <h2 className="text-2xl font-bold text-white flex items-center">
            <Activity className="mr-2 text-blue-400" />
            AI Treatment Optimization
          </h2>
          <p className="text-slate-400 text-sm mt-1">Personalized recommendations based on clinical history and predictive models.</p>
        </div>
        <div className="flex space-x-2">
          <button 
            onClick={fetchRecommendations}
            className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg transition-colors text-sm font-medium flex items-center"
          >
            <RefreshCw className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
            Refresh
          </button>
          <button 
            onClick={handleReanalyze}
            disabled={loading}
            className="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors text-sm font-medium disabled:opacity-50"
          >
            Re-analyze
          </button>
        </div>
      </div>

      {/* Main Content */}
      <div className="p-6 space-y-8">
        {/* Scores */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <ScoreCard title="Effectiveness" score={recommendation.effectiveness_score} icon={TrendingUp} color="bg-green-600" />
          <ScoreCard title="Safety Profile" score={recommendation.safety_score} icon={Shield} color="bg-blue-600" />
          <ScoreCard title="Cost Efficiency" score={recommendation.cost_efficiency_score} icon={DollarSign} color="bg-purple-600" />
        </div>

        {/* Recommendations */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div className="space-y-4">
            <h3 className="text-lg font-semibold text-white flex items-center">
              <CheckCircle className="mr-2 text-green-400 w-5 h-5" />
              Recommended Medications
            </h3>
            <div className="space-y-3">
              {recommendation.recommended_medications.map((med, idx) => (
                <div key={idx} className="p-4 bg-slate-800/50 border border-slate-700 rounded-xl hover:border-blue-500/50 transition-all group">
                  <div className="flex justify-between items-start">
                    <div>
                      <h4 className="font-bold text-blue-400">{med.name}</h4>
                      <p className="text-sm text-slate-300 mt-1">{med.dosage} • {med.frequency}</p>
                    </div>
                    <button className="p-2 text-slate-500 hover:text-white transition-colors">
                      <ChevronRight className="w-5 h-5" />
                    </button>
                  </div>
                  <div className="mt-3 p-3 bg-blue-500/10 rounded-lg border border-blue-500/20">
                    <p className="text-xs text-blue-300 flex items-start">
                      <Info className="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" />
                      {med.justification}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="space-y-6">
            {/* Predictions */}
            <div className="p-5 bg-slate-800/50 border border-slate-700 rounded-xl space-y-4">
              <h3 className="text-md font-semibold text-white flex items-center">
                <TrendingUp className="mr-2 text-blue-400 w-5 h-5" />
                Outcome Predictions
              </h3>
              <div className="grid grid-cols-2 gap-4">
                <div className="p-3 bg-slate-900 rounded-lg border border-slate-700">
                  <p className="text-xs text-slate-400">Success Rate</p>
                  <p className="text-lg font-bold text-green-400">{recommendation.outcome_predictions.success_rate}</p>
                </div>
                <div className="p-3 bg-slate-900 rounded-lg border border-slate-700">
                  <p className="text-xs text-slate-400">Time to Effect</p>
                  <p className="text-lg font-bold text-blue-400">{recommendation.outcome_predictions.time_to_effect}</p>
                </div>
              </div>
              <p className="text-sm text-slate-300 italic">"{recommendation.outcome_predictions.primary_benefit}"</p>
            </div>

            {/* Risk Assessment */}
            <div className="p-5 bg-orange-500/5 border border-orange-500/20 rounded-xl space-y-4">
              <div className="flex justify-between items-center">
                <h3 className="text-md font-semibold text-white flex items-center">
                  <AlertTriangle className="mr-2 text-orange-400 w-5 h-5" />
                  Risk Assessment
                </h3>
                <span className="px-2 py-1 bg-green-500/20 text-green-400 text-xs font-bold rounded uppercase tracking-wider">
                  {recommendation.risk_assessment.level} Risk
                </span>
              </div>
              <ul className="space-y-2">
                {recommendation.risk_assessment.factors.map((factor, idx) => (
                  <li key={idx} className="text-sm text-slate-300 flex items-start">
                    <span className="w-1.5 h-1.5 rounded-full bg-orange-400 mt-1.5 mr-2 flex-shrink-0"></span>
                    {factor}
                  </li>
                ))}
              </ul>
              <div className="mt-4 p-3 bg-slate-900/50 rounded-lg border border-slate-700">
                <p className="text-xs text-slate-400 uppercase font-bold tracking-widest mb-1">Mitigation Strategy</p>
                <p className="text-sm text-slate-300">{recommendation.risk_assessment.mitigation}</p>
              </div>
            </div>
          </div>
        </div>

        {/* Action Bar */}
        <div className="pt-6 border-t border-slate-800 flex justify-end space-x-4">
          <button 
            onClick={handleReject}
            disabled={actionLoading || !recommendation}
            className="px-6 py-3 bg-slate-800 hover:bg-red-900/30 text-slate-300 hover:text-red-400 rounded-xl transition-all font-bold flex items-center disabled:opacity-50"
          >
            <XCircle className="mr-2 w-5 h-5" />
            {actionLoading ? 'Processing...' : 'Reject All'}
          </button>
          <button 
            onClick={handleValidate}
            disabled={actionLoading || !recommendation}
            className="px-8 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-xl transition-all font-bold shadow-lg shadow-blue-600/20 flex items-center disabled:opacity-50"
          >
            <CheckCircle className="mr-2 w-5 h-5" />
            {actionLoading ? 'Implementing...' : 'Validate & Implement Plan'}
          </button>
        </div>
      </div>
    </div>
  );
};

export default TreatmentOptimization;
