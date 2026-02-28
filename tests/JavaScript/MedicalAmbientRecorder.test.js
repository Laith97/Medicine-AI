// tests/javascript/MedicalAmbientRecorder.test.js

// Mock browser APIs that are used by MedicalAmbientRecorder
const mockMediaStream = {
  getTracks: () => [{ stop: jest.fn() }]
};

const mockAudioContext = {
  state: 'running',
  createMediaStreamSource: jest.fn(),
  createScriptProcessor: jest.fn(),
  close: jest.fn(),
  resume: jest.fn().mockResolvedValue(),
  destination: {}
};

const mockAudioSource = {
  connect: jest.fn(),
  disconnect: jest.fn()
};

const mockAudioProcessor = {
  connect: jest.fn(),
  disconnect: jest.fn(),
  onaudioprocess: null
};

global.AudioContext = jest.fn(() => mockAudioContext);
global.webkitAudioContext = global.AudioContext;

global.navigator.mediaDevices = {
  getUserMedia: jest.fn().mockResolvedValue(mockMediaStream)
};

// Mock WebSocket
const mockWebSocket = {
  send: jest.fn(),
  close: jest.fn(),
  readyState: WebSocket.OPEN,
  onopen: null,
  onmessage: null,
  onclose: null,
  onerror: null
};

global.WebSocket = jest.fn(() => mockWebSocket);

// Import the class to test
const { MedicalAmbientRecorder } = require('../../resources/js/utils/MedicalAmbientRecorder');

describe('MedicalAmbientRecorder', () => {
  let recorder;

  beforeEach(() => {
    jest.clearAllMocks();
    
    // Reset mock WebSocket
    Object.assign(mockWebSocket, {
      send: jest.fn(),
      close: jest.fn(),
      readyState: WebSocket.OPEN,
      onopen: null,
      onmessage: null,
      onclose: null,
      onerror: null
    });
  });

  describe('constructor', () => {
    test('should initialize with default configuration', () => {
      recorder = new MedicalAmbientRecorder();
      
      expect(recorder.isRecording).toBe(false);
      expect(recorder.isDestroyed).toBe(false);
      expect(recorder.config.sampleRate).toBe(16000);
    });

    test('should accept custom configuration', () => {
      const config = { sampleRate: 22050, bufferSize: 2048 };
      recorder = new MedicalAmbientRecorder(config);
      
      expect(recorder.config.sampleRate).toBe(22050);
      expect(recorder.config.bufferSize).toBe(2048);
    });
  });

  describe('input validation', () => {
    beforeEach(() => {
      recorder = new MedicalAmbientRecorder();
    });

    test('should validate valid UUID visitId', () => {
      const validUUID = '550e8400-e29b-41d4-a716-446655440000';
      expect(recorder.isValidVisitId(validUUID)).toBe(true);
    });

    test('should validate numeric visitId', () => {
      expect(recorder.isValidVisitId('123')).toBe(true);
      expect(recorder.isValidVisitId('0')).toBe(true);
    });

    test('should reject invalid visitId', () => {
      expect(recorder.isValidVisitId(null)).toBe(false);
      expect(recorder.isValidVisitId(undefined)).toBe(false);
      expect(recorder.isValidVisitId('')).toBe(false);
      expect(recorder.isValidVisitId('invalid-uuid')).toBe(false);
      expect(recorder.isValidVisitId(123)).toBe(false); // Should be string
    });

    test('should validate JWT token format', () => {
      const jwtToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';
      expect(recorder.isValidAuthToken(jwtToken)).toBe(true);
    });

    test('should validate UUID token format', () => {
      const uuidToken = '550e8400-e29b-41d4-a716-446655440000';
      expect(recorder.isValidAuthToken(uuidToken)).toBe(true);
    });

    test('should reject invalid auth tokens', () => {
      expect(recorder.isValidAuthToken(null)).toBe(false);
      expect(recorder.isValidAuthToken(undefined)).toBe(false);
      expect(recorder.isValidAuthToken('')).toBe(false);
      expect(recorder.isValidAuthToken('short')).toBe(false);
      expect(recorder.isValidAuthToken(123)).toBe(false); // Should be string
    });
  });

  describe('startRecording', () => {
    beforeEach(() => {
      recorder = new MedicalAmbientRecorder();
    });

    test('should start recording with valid parameters', async () => {
      const mockProcessor = {
        onaudioprocess: null,
        connect: jest.fn(),
        disconnect: jest.fn()
      };
      
      // Mock the audio processing pipeline creation
      jest.spyOn(recorder, 'createAudioProcessingPipeline').mockResolvedValue();
      jest.spyOn(recorder, 'connectWebSocket').mockResolvedValue();
      
      const visitId = '123';
      const authToken = 'valid-token';
      
      await expect(recorder.startRecording(visitId, authToken)).resolves.not.toThrow();
      
      expect(recorder.isRecording).toBe(true);
      expect(recorder.visitId).toBe(visitId);
      expect(recorder.authToken).toBe(authToken);
    });

    test('should reject invalid parameters', async () => {
      await expect(recorder.startRecording(null, 'token')).rejects.toThrow('Invalid visitId provided');
      await expect(recorder.startRecording('123', null)).rejects.toThrow('Invalid authToken provided');
      await expect(recorder.startRecording('', '')).rejects.toThrow();
    });

    test('should handle microphone permission denial', async () => {
      // Mock getUserMedia to reject
      navigator.mediaDevices.getUserMedia = jest.fn().mockRejectedValue(new Error('Permission denied'));
      
      jest.spyOn(recorder, 'connectWebSocket').mockResolvedValue();
      
      await expect(recorder.startRecording('123', 'valid-token')).rejects.toThrow();
    });
  });

  describe('stopRecording', () => {
    beforeEach(() => {
      recorder = new MedicalAmbientRecorder();
    });

    test('should stop recording and cleanup resources', () => {
      // Set recording state
      recorder.isRecording = true;
      recorder.mediaStream = mockMediaStream;
      recorder.processor = mockAudioProcessor;
      recorder.source = mockAudioSource;
      recorder.audioContext = mockAudioContext;
      recorder.websocket = mockWebSocket;
      
      recorder.stopRecording();
      
      expect(recorder.isRecording).toBe(false);
      expect(mockMediaStream.getTracks()[0].stop).toHaveBeenCalled();
      expect(mockAudioContext.close).toHaveBeenCalled();
      expect(mockWebSocket.close).toHaveBeenCalled();
    });
  });

  describe('convertFloat32ToInt16', () => {
    beforeEach(() => {
      recorder = new MedicalAmbientRecorder();
    });

    test('should correctly convert float32 array to int16', () => {
      const float32Array = new Float32Array([0.5, -0.5, 0.0, 1.0, -1.0]);
      const result = recorder.convertFloat32ToInt16(float32Array);
      
      expect(result).toBeInstanceOf(Int16Array);
      expect(result[0]).toBe(16384);  // 0.5 * 0x7FFF
      expect(result[1]).toBe(-16384); // -0.5 * 0x7FFF
      expect(result[2]).toBe(0);
      expect(result[3]).toBe(32767);  // 1.0 * 0x7FFF clamped
      expect(result[4]).toBe(-32768); // -1.0 * 0x7FFF clamped
    });

    test('should handle edge cases', () => {
      const emptyArray = new Float32Array([]);
      const result = recorder.convertFloat32ToInt16(emptyArray);
      
      expect(result).toBeInstanceOf(Int16Array);
      expect(result.length).toBe(0);
      
      // Test with values outside [-1, 1] range
      const outOfRangeArray = new Float32Array([2.0, -2.0]);
      const outOfRangeResult = recorder.convertFloat32ToInt16(outOfRangeArray);
      
      expect(outOfRangeResult[0]).toBe(32767);  // Clamped to max
      expect(outOfRangeResult[1]).toBe(-32768); // Clamped to min
    });
  });

  describe('audio processing', () => {
    beforeEach(() => {
      recorder = new MedicalAmbientRecorder();
      recorder.isRecording = true;
      recorder.websocket = mockWebSocket;
    });

    test('should process audio data correctly when recording', () => {
      const mockAudioBuffer = {
        getChannelData: jest.fn().mockReturnValue(new Float32Array([0.5, 0.5]))
      };
      
      const mockEvent = {
        inputBuffer: mockAudioBuffer
      };
      
      // Initialize processor to set up onaudioprocess
      mockAudioContext.createScriptProcessor.mockReturnValue(mockAudioProcessor);
      mockAudioContext.createMediaStreamSource.mockReturnValue(mockAudioSource);
      
      return recorder.createAudioProcessingPipeline().then(() => {
        // Simulate audio processing event
        mockAudioProcessor.onaudioprocess(mockEvent);
        
        // Verify that audio data was processed and sent
        expect(mockAudioBuffer.getChannelData).toHaveBeenCalledWith(0);
        expect(mockWebSocket.send).toHaveBeenCalled();
      });
    });
  });

  describe('reconnection logic', () => {
    beforeEach(() => {
      recorder = new MedicalAmbientRecorder();
    });

    test('should attempt reconnection when connection closes', () => {
      // Mock setTimeout to run immediately
      jest.useFakeTimers();
      const setTimeoutSpy = jest.spyOn(global, 'setTimeout');
      
      // Set up recording state
      recorder.isRecording = true;
      recorder.websocket = mockWebSocket;
      
      // Set reconnection attempts to less than max
      recorder.reconnectAttempts = 0;
      recorder.maxReconnectAttempts = 5;
      
      // Mock connectWebSocket to resolve
      jest.spyOn(recorder, 'connectWebSocket').mockResolvedValue();
      
      // Simulate connection close
      mockWebSocket.onclose();
      
      // Fast-forward timers
      jest.runAllTimers();
      
      expect(setTimeoutSpy).toHaveBeenCalled();
      expect(recorder.reconnectAttempts).toBe(1);
      
      jest.useRealTimers();
    });

    test('should stop reconnecting if max attempts reached', () => {
      recorder.isRecording = true;
      recorder.reconnectAttempts = 5; // Already at max
      recorder.maxReconnectAttempts = 5;
      
      // Mock error handler
      const onErrorSpy = jest.fn();
      recorder.onError = onErrorSpy;
      
      // Simulate connection close
      mockWebSocket.onclose();
      
      expect(onErrorSpy).toHaveBeenCalledWith('Max reconnection attempts reached', expect.any(Error));
    });
  });

  describe('destroy', () => {
    test('should cleanup all resources and prevent further operations', () => {
      recorder = new MedicalAmbientRecorder();
      recorder.isRecording = true;
      
      const stopRecordingSpy = jest.spyOn(recorder, 'stopRecording');
      
      recorder.destroy();
      
      expect(recorder.isDestroyed).toBe(true);
      expect(stopRecordingSpy).toHaveBeenCalled();
    });
  });
});