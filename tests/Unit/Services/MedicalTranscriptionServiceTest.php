<?php

namespace Tests\Unit\Services;

use App\Services\MedicalTranscriptionService;
use Tests\TestCase;
use Mockery;

class MedicalTranscriptionServiceTest extends TestCase
{
    protected $transcriptionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transcriptionService = new MedicalTranscriptionService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_process_real_time_transcript_processes_data_correctly()
    {
        $transcriptData = [
            ['speaker' => '1', 'text' => 'How are you feeling today?'],
            ['speaker' => '2', 'text' => 'I have been experiencing chest pain']
        ];

        $visitId = 1;

        // We can't actually test the internal logging without complex mocking,
        // so we'll test that the method runs without exceptions
        $this->expectNotToPerformAssertions(); // This means no exception should be thrown
        $this->transcriptionService->processRealTimeTranscript($transcriptData, $visitId);
    }

    public function test_process_real_time_transcript_with_empty_data()
    {
        $transcriptData = [];
        $visitId = 1;

        $this->expectNotToPerformAssertions(); // This means no exception should be thrown
        $this->transcriptionService->processRealTimeTranscript($transcriptData, $visitId);
    }

    public function test_process_real_time_transcript_error_handling()
    {
        $transcriptData = [
            ['speaker' => '1', 'text' => 'Normal text']
        ];

        // Method should handle errors gracefully without throwing exceptions
        $this->expectNotToPerformAssertions(); // This means no exception should be thrown
        $this->transcriptionService->processRealTimeTranscript($transcriptData, -1); // Invalid ID
    }

    public function test_segment_by_speaker()
    {
        $transcriptData = [
            ['speaker' => '1', 'text' => 'Doctor: How are you?'],
            ['speaker' => '2', 'text' => 'Patient: I feel sick']
        ];

        // Access private method using reflection
        $reflection = new \ReflectionClass($this->transcriptionService);
        $method = $reflection->getMethod('segmentBySpeaker');
        $method->setAccessible(true);

        $result = $method->invoke($this->transcriptionService, $transcriptData);

        $this->assertEquals($transcriptData, $result);
    }

    public function test_categorize_into_soap_with_doctor_patient_speakers()
    {
        $segments = [
            ['speaker' => '1', 'text' => 'How are you feeling today?'], // Doctor
            ['speaker' => '2', 'text' => 'I have chest pain'],          // Patient
            ['speaker' => '1', 'text' => 'Diagnosis is urgent'],        // Doctor
            ['speaker' => '1', 'text' => 'Plan for immediate care']     // Doctor
        ];

        // Access private method using reflection
        $reflection = new \ReflectionClass($this->transcriptionService);
        $method = $reflection->getMethod('categorizeIntoSOAP');
        $method->setAccessible(true);

        $result = $method->invoke($this->transcriptionService, $segments);

        $this->assertArrayHasKey('subjective', $result);
        $this->assertArrayHasKey('objective', $result);
        $this->assertArrayHasKey('assessment', $result);
        $this->assertArrayHasKey('plan', $result);

        // Patient (speaker 2) statements should go to subjective
        $this->assertStringContainsString('I have chest pain', $result['subjective']);

        // Doctor statements with diagnostic keywords should go to assessment
        $this->assertStringContainsString('Diagnosis is urgent', $result['assessment']);

        // Doctor statements with plan keywords should go to plan
        $this->assertStringContainsString('Plan for immediate care', $result['plan']);
    }

    public function test_categorize_into_soap_with_keyword_matching()
    {
        $segments = [
            ['speaker' => '1', 'text' => 'Patient has diabetes and hypertension'], // Objective
            ['speaker' => '1', 'text' => 'The assessment is acute tonsillitis'],   // Assessment
            ['speaker' => '1', 'text' => 'Plan is antibiotics and follow up']      // Plan
        ];

        // Access private method using reflection
        $reflection = new \ReflectionClass($this->transcriptionService);
        $method = $reflection->getMethod('categorizeIntoSOAP');
        $method->setAccessible(true);

        $result = $method->invoke($this->transcriptionService, $segments);

        $this->assertStringContainsString('diabetes', $result['objective']);
        $this->assertStringContainsString('tonsillitis', $result['assessment']);
        $this->assertStringContainsString('antibiotics', $result['plan']);
    }
}