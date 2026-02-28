import { useEffect, useRef, useCallback } from 'react';
import { io, Socket } from 'socket.io-client';
import { RealTimeUpdate } from '../types/dashboard';

export const useRealTimeUpdates = (
  dashboardId: string,
  onUpdate: (update: RealTimeUpdate) => void
) => {
  const socketRef = useRef<Socket | null>(null);

  const connect = useCallback(() => {
    if (!socketRef.current) {
      socketRef.current = io('/analytics', {
        auth: {
          token: localStorage.getItem('auth_token'),
        },
      });

      socketRef.current.on('connect', () => {
        console.log('Connected to real-time updates');

        // Subscribe to dashboard updates
        socketRef.current?.emit('subscribe_dashboard', {
          dashboard_id: dashboardId,
          components: ['revenue_card', 'satisfaction_chart', 'appointments_chart'],
        });
      });

      socketRef.current.on('dashboard_update', (data: RealTimeUpdate) => {
        onUpdate(data);
      });

      socketRef.current.on('disconnect', () => {
        console.log('Disconnected from real-time updates');
      });

      socketRef.current.on('connect_error', (error) => {
        console.error('Real-time connection error:', error);
      });
    }
  }, [dashboardId, onUpdate]);

  const disconnect = useCallback(() => {
    if (socketRef.current) {
      socketRef.current.disconnect();
      socketRef.current = null;
    }
  }, []);

  useEffect(() => {
    return () => {
      disconnect();
    };
  }, [disconnect]);

  return { connect, disconnect };
};
