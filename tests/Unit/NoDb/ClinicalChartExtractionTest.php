<?php

namespace Tests\Unit\NoDb;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\VoiceAssistantController;
use Illuminate\Support\Facades\Cache;
use OpenAI\Laravel\Facades\OpenAI;
use ReflectionClass;

class ClinicalChartExtractionTest extends TestCase
{
    private VoiceAssistantController $controller;
    private ReflectionClass $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new VoiceAssistantController();
        $this->ref = new ReflectionClass($this->controller);
    }

    // ---- extractJsonFromResponse ----

    public function test_extract_json_direct()
    {
        $m = $this->ref->getMethod('extractJsonFromResponse');
        $m->setAccessible(true);
        $json = '{"symptoms":"nasal congestion x5 days","medical_history":"NKDA","physical_findings":"erythematous mucosa","medications":"None","vital_signs":"BP 122/78","diagnosis":"Acute sinusitis","care_plan":"Antibiotics 7d"}';
        $res = $m->invoke($this->controller, $json);
        $this->assertEquals('nasal congestion x5 days', $res['symptoms']);
        $this->assertEquals('BP 122/78', $res['vital_signs']);
        $this->assertCount(7, $res);
    }

    public function test_extract_json_wrapped_in_text()
    {
        $m = $this->ref->getMethod('extractJsonFromResponse');
        $m->setAccessible(true);
        $wrapped = 'Here is your JSON: {"symptoms":"fever","medical_history":"","physical_findings":"","medications":"","vital_signs":"","diagnosis":"","care_plan":""} thanks';
        $res = $m->invoke($this->controller, $wrapped);
        $this->assertEquals('fever', $res['symptoms']);
    }

    public function test_extract_json_code_fence()
    {
        $m = $this->ref->getMethod('extractJsonFromResponse');
        $m->setAccessible(true);
        $fenced = "```json\n{\"symptoms\":\"headache\",\"medical_history\":\"\",\"physical_findings\":\"\",\"medications\":\"\",\"vital_signs\":\"BP 120/80\",\"diagnosis\":\"\",\"care_plan\":\"\"}\n```";
        $res = $m->invoke($this->controller, $fenced);
        $this->assertEquals('headache', $res['symptoms']);
        $this->assertEquals('BP 120/80', $res['vital_signs']);
    }

    public function test_extract_json_invalid_returns_null()
    {
        $m = $this->ref->getMethod('extractJsonFromResponse');
        $m->setAccessible(true);
        $res = $m->invoke($this->controller, 'not json at all');
        $this->assertNull($res);
    }

    public function test_extract_json_emojis_and_markdown_rejected()
    {
        $m = $this->ref->getMethod('extractJsonFromResponse');
        $m->setAccessible(true);
        $md = "🟢 LEVEL 1: QUICK CLINICAL SUMMARY\n**Symptoms:** fever";
        $res = $m->invoke($this->controller, $md);
        $this->assertNull($res);
    }

    // ---- validateAndClean ----

    public function test_validate_and_clean_fills_missing_keys()
    {
        $m = $this->ref->getMethod('validateAndCleanExtractedData');
        $m->setAccessible(true);
        $input = ['symptoms' => ' fever ', 'medical_history' => null, 'extra' => 'ignore'];
        $clean = $m->invoke($this->controller, $input);
        $this->assertEquals('fever', $clean['symptoms']);
        $this->assertEquals('', $clean['medical_history']);
        $this->assertArrayHasKey('physical_findings', $clean);
        $this->assertCount(7, $clean);
        $this->assertArrayNotHasKey('extra', $clean);
    }

    public function test_validate_and_clean_trims()
    {
        $m = $this->ref->getMethod('validateAndCleanExtractedData');
        $m->setAccessible(true);
        $clean = $m->invoke($this->controller, ['symptoms'=>'  cough  ','medical_history'=>'  NKDA ','physical_findings'=>'','medications'=>'','vital_signs'=>'','diagnosis'=>'','care_plan'=>'']);
        $this->assertEquals('cough', $clean['symptoms']);
        $this->assertEquals('NKDA', $clean['medical_history']);
    }

    // ---- parseKeyValueResponse ----

    public function test_parse_key_value_response()
    {
        $m = $this->ref->getMethod('parseKeyValueResponse');
        $m->setAccessible(true);
        $txt = 'symptoms: "headache and fever" medical_history: "none" vital_signs: "BP 122/78"';
        $res = $m->invoke($this->controller, $txt);
        $this->assertEquals('headache and fever', $res['symptoms']);
        $this->assertEquals('none', $res['medical_history']);
        $this->assertEquals('BP 122/78', $res['vital_signs']);
    }

    // ---- generateFallbackData ----

    public function test_generate_fallback_khalid_transcript()
    {
        $m = $this->ref->getMethod('generateFallbackData');
        $m->setAccessible(true);
        $transcript = "Good morning Khalid, nasal congestion, facial pressure and headache for about 5 days, thick discharge, low-grade fever 38.1, sore throat, mild cough. No known drug allergies, no diabetes, no hypertension, smoked but quit 2 years ago. On exam BP 122/78, HR 78, Temp 37.9, SpO2 98%, weight 82kg. Erythematous nasal mucosa, maxillary sinus tenderness.";
        $res = $m->invoke($this->controller, $transcript);
        $this->assertNotEmpty($res['symptoms'], 'symptoms should be extracted via fallback');
        $this->assertStringContainsString('congestion', $res['symptoms']);
        $this->assertNotEmpty($res['medical_history']);
        $this->assertNotEmpty($res['vital_signs']);
        $this->assertNotEmpty($res['diagnosis']);
        $this->assertCount(7, $res);
    }

    public function test_generate_fallback_empty_transcript()
    {
        $m = $this->ref->getMethod('generateFallbackData');
        $m->setAccessible(true);
        $res = $m->invoke($this->controller, '');
        $this->assertEquals('', $res['symptoms']);
        $this->assertEquals('', $res['diagnosis']);
    }

    public function test_generate_fallback_vitals_regex()
    {
        $m = $this->ref->getMethod('generateFallbackData');
        $m->setAccessible(true);
        $res = $m->invoke($this->controller, 'Vitals BP 122/78 HR 78 Temp 37.9C SpO2 98%');
        $this->assertNotEmpty($res['vital_signs']);
        $this->assertStringContainsString('122/78', $res['vital_signs']);
    }

    // ---- extractMedicalDataFromText robustness ----
    // We test the private method's fallback path without hitting OpenAI by mocking Cache and causing exception
    public function test_extract_medical_data_returns_valid_structure_even_when_transcript_short()
    {
        $m = $this->ref->getMethod('extractMedicalDataFromText');
        $m->setAccessible(true);
        // Short transcript triggers fallback via early return
        $res = $m->invoke($this->controller, 'hi');
        $this->assertIsArray($res);
        $this->assertCount(7, $res);
        $this->assertArrayHasKey('symptoms', $res);
        $this->assertArrayHasKey('vital_signs', $res);
    }

    public function test_extract_medical_data_empty_returns_fallback_structure()
    {
        $m = $this->ref->getMethod('extractMedicalDataFromText');
        $m->setAccessible(true);
        $res = $m->invoke($this->controller, '');
        $this->assertIsArray($res);
        $this->assertCount(7, $res);
        foreach (['symptoms','medical_history','physical_findings','medications','vital_signs','diagnosis','care_plan'] as $k) {
            $this->assertArrayHasKey($k, $res);
        }
    }

    // ---- ensures 7 fields contract ----
    public function test_all_extractors_return_seven_keys()
    {
        $methods = ['extractJsonFromResponse','validateAndCleanExtractedData','generateFallbackData','parseKeyValueResponse'];
        foreach (['validateAndCleanExtractedData','generateFallbackData','parseKeyValueResponse'] as $method) {
            $m = $this->ref->getMethod($method);
            $m->setAccessible(true);
            $sample = $method === 'validateAndCleanExtractedData' ? ['symptoms'=>'x'] : 'symptoms: "x"';
            if ($method === 'generateFallbackData') $sample = 'fever headache BP 120/80';
            $res = $m->invoke($this->controller, $sample);
            if ($res !== null) {
                $this->assertCount(7, $res, "Method $method should return 7 keys");
            }
        }
    }

    // ---- real Khalid JSON mock without OpenAI ----
    public function test_khalid_real_case_json_is_correctly_parsed_and_cleaned()
    {
        $jsonM = $this->ref->getMethod('extractJsonFromResponse');
        $jsonM->setAccessible(true);
        $cleanM = $this->ref->getMethod('validateAndCleanExtractedData');
        $cleanM->setAccessible(true);

        // Simulate what fixed OpenAI will return for Khalid
        $mockAiJson = '{"symptoms":"Nasal congestion x5 days, facial pressure, headache, thick yellow discharge, low-grade fever 38.1C, sore throat, mild cough, pressure on bending","medical_history":"No known drug allergies, no diabetes, no hypertension, ex-smoker quit 2y","physical_findings":"Erythematous nasal mucosa, maxillary sinus tenderness, no edema","medications":"None (no regular meds)","vital_signs":"BP 122/78, HR 78, Temp 37.9C, SpO2 98%, Wt 82kg","diagnosis":"Acute bacterial sinusitis","care_plan":"Amoxicillin-Clavulanate 875/125 BID 7d, saline irrigation, follow-up 1 week, red flag education"}';

        $parsed = $jsonM->invoke($this->controller, $mockAiJson);
        $clean = $cleanM->invoke($this->controller, $parsed);

        $this->assertEquals('Nasal congestion x5 days, facial pressure, headache, thick yellow discharge, low-grade fever 38.1C, sore throat, mild cough, pressure on bending', $clean['symptoms']);
        $this->assertEquals('No known drug allergies, no diabetes, no hypertension, ex-smoker quit 2y', $clean['medical_history']);
        $this->assertStringContainsString('Erythematous', $clean['physical_findings']);
        $this->assertStringContainsString('None', $clean['medications']);
        $this->assertStringContainsString('BP 122/78', $clean['vital_signs']);
        $this->assertStringContainsString('sinusitis', strtolower($clean['diagnosis']));
        $this->assertStringContainsString('Amoxicillin', $clean['care_plan']);
        // Ensure no LEVEL header leak
        foreach ($clean as $v) {
            $this->assertStringNotContainsString('LEVEL', $v);
            $this->assertStringNotContainsString('🟢', $v);
        }
    }
}
