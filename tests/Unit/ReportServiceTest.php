<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ReportService;
use Illuminate\Support\Facades\Storage;

class ReportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::makeDirectory('data');
        
        // Create minimal test data
        Storage::put('data/students.json', '[]');
        Storage::put('data/assessments.json', '[]');
        Storage::put('data/questions.json', '[]');
        Storage::put('data/student-responses.json', '[]');
    }

    protected function tearDown(): void
    {
        Storage::deleteDirectory('data');
        parent::tearDown();
    }

    public function test_service_instantiates_without_error()
    {
        $service = new ReportService();
        $this->assertInstanceOf(ReportService::class, $service);
    }

    public function test_handles_missing_data_files_gracefully()
    {
        Storage::deleteDirectory('data');
        
        $service = new ReportService();
        $report = $service->generateDiagnosticReport('any-student');
        
        $this->assertArrayHasKey('error', $report);
    }
}