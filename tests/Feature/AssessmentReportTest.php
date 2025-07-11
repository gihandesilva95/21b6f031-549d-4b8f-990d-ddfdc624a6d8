<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class AssessmentReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data directory
        Storage::makeDirectory('data');
        
        // Create sample test data
        $this->createTestData();
    }

    protected function tearDown(): void
    {
        // Clean up test data
        Storage::deleteDirectory('data');
        parent::tearDown();
    }

    private function createTestData()
    {
        // Sample students data
        $students = [
            [
                'id' => 'student1',
                'firstName' => 'Tony',
                'lastName' => 'Stark',
                'yearLevel' => 6
            ],
            [
                'id' => 'student2',
                'firstName' => 'Steve',
                'lastName' => 'Rogers',
                'yearLevel' => 6
            ]
        ];

        // Sample assessments data
        $assessments = [
            [
                'id' => 'assessment1',
                'name' => 'Numeracy',
                'questions' => [
                    ['questionId' => 'question1', 'position' => 1]
                ]
            ]
        ];

        // Sample questions data
        $questions = [
            [
                'id' => 'question1',
                'stem' => 'What is 2 + 2?',
                'type' => 'multiple-choice',
                'strand' => 'Number and Algebra',
                'config' => [
                    'options' => [
                        ['id' => 'option1', 'label' => 'A', 'value' => '3'],
                        ['id' => 'option2', 'label' => 'B', 'value' => '4'],
                        ['id' => 'option3', 'label' => 'C', 'value' => '5']
                    ],
                    'key' => 'option2',
                    'hint' => 'Simple addition: 2 + 2 = 4'
                ]
            ],
            [
                'id' => 'question2',
                'stem' => 'What is the median of 5, 21, 7, 18, 9?',
                'type' => 'multiple-choice',
                'strand' => 'Statistics and Probability',
                'config' => [
                    'options' => [
                        ['id' => 'option1', 'label' => 'A', 'value' => '7'],
                        ['id' => 'option2', 'label' => 'B', 'value' => '9'],
                        ['id' => 'option3', 'label' => 'C', 'value' => '18']
                    ],
                    'key' => 'option2',
                    'hint' => 'You must first arrange the numbers in ascending order. The median is the middle term, which in this case is 9'
                ]
            ],
            [
                'id' => 'question3',
                'stem' => 'What is the area of a circle with radius 5?',
                'type' => 'multiple-choice',
                'strand' => 'Measurement and Geometry',
                'config' => [
                    'options' => [
                        ['id' => 'option1', 'label' => 'A', 'value' => '25π'],
                        ['id' => 'option2', 'label' => 'B', 'value' => '10π'],
                        ['id' => 'option3', 'label' => 'C', 'value' => '5π']
                    ],
                    'key' => 'option1',
                    'hint' => 'Area of circle = πr², so π × 5² = 25π'
                ]
            ]
        ];

        // Sample responses data
        $responses = [
            [
                'id' => 'response1',
                'assessmentId' => 'assessment1',
                'assigned' => '14/12/2019 10:31:00',
                'started' => '16/12/2019 10:00:00',
                'completed' => '16/12/2019 10:46:00',
                'student' => ['id' => 'student1', 'yearLevel' => 3],
                'responses' => [
                    ['questionId' => 'question1', 'response' => 'option1'], // Wrong
                    ['questionId' => 'question2', 'response' => 'option1'], // Wrong
                    ['questionId' => 'question3', 'response' => 'option2']  // Wrong
                ],
                'results' => ['rawScore' => 0]
            ],
            [
                'id' => 'response2',
                'assessmentId' => 'assessment1',
                'assigned' => '14/12/2020 10:31:00',
                'started' => '16/12/2020 10:00:00',
                'completed' => '16/12/2020 10:46:00',
                'student' => ['id' => 'student1', 'yearLevel' => 4],
                'responses' => [
                    ['questionId' => 'question1', 'response' => 'option2'], // Correct
                    ['questionId' => 'question2', 'response' => 'option1'], // Wrong
                    ['questionId' => 'question3', 'response' => 'option2']  // Wrong
                ],
                'results' => ['rawScore' => 1]
            ],
            [
                'id' => 'response3',
                'assessmentId' => 'assessment1',
                'assigned' => '14/12/2021 10:31:00',
                'started' => '16/12/2021 10:00:00',
                'completed' => '16/12/2021 10:46:00',
                'student' => ['id' => 'student1', 'yearLevel' => 5],
                'responses' => [
                    ['questionId' => 'question1', 'response' => 'option2'], // Correct
                    ['questionId' => 'question2', 'response' => 'option1'], // Wrong - this should show in feedback
                    ['questionId' => 'question3', 'response' => 'option1']  // Correct
                ],
                'results' => ['rawScore' => 2]
            ],
            [
                'id' => 'response4',
                'assessmentId' => 'assessment1',
                'assigned' => '14/12/2019 10:31:00',
                'started' => '16/12/2019 10:00:00',
                // No completed field = incomplete assessment
                'student' => ['id' => 'student2', 'yearLevel' => 3],
                'responses' => [
                    ['questionId' => 'question1', 'response' => 'option2'],
                    ['questionId' => 'question2', 'response' => 'option2'],
                    ['questionId' => 'question3', 'response' => 'option1']
                ],
                'results' => ['rawScore' => 3]
            ]
        ];

        // Save test data to storage
        Storage::put('data/students.json', json_encode($students, JSON_PRETTY_PRINT));
        Storage::put('data/assessments.json', json_encode($assessments, JSON_PRETTY_PRINT));
        Storage::put('data/questions.json', json_encode($questions, JSON_PRETTY_PRINT));
        Storage::put('data/student-responses.json', json_encode($responses, JSON_PRETTY_PRINT));
    }

    public function test_diagnostic_report_generation()
    {
        $reportService = new ReportService();
        $report = $reportService->generateDiagnosticReport('student1');

        $this->assertArrayNotHasKey('error', $report);
        $this->assertEquals('Tony', $report['student']['firstName']);
        $this->assertEquals('Stark', $report['student']['lastName']);
        $this->assertEquals('Numeracy', $report['assessment']['name']);
        $this->assertEquals(2, $report['total_correct']); // Most recent: 2 correct
        $this->assertEquals(3, $report['total_questions']);
        
        // Check strand scores
        $this->assertArrayHasKey('Number and Algebra', $report['strand_scores']);
        $this->assertArrayHasKey('Statistics and Probability', $report['strand_scores']);
        $this->assertArrayHasKey('Measurement and Geometry', $report['strand_scores']);
    }

    public function test_progress_report_generation()
    {
        $reportService = new ReportService();
        $report = $reportService->generateProgressReport('student1');

        $this->assertArrayNotHasKey('error', $report);
        $this->assertEquals('Tony', $report['student']['firstName']);
        $this->assertEquals('Numeracy', $report['assessment_name']);
        $this->assertEquals(3, $report['total_completions']);
        $this->assertEquals(2, $report['improvement']); // From 0 to 2 correct
    }

    public function test_feedback_report_generation()
    {
        $reportService = new ReportService();
        $report = $reportService->generateFeedbackReport('student1');

        $this->assertArrayNotHasKey('error', $report);
        $this->assertEquals('Tony', $report['student']['firstName']);
        $this->assertEquals(2, $report['total_correct']); // Most recent: 2 correct
        $this->assertEquals(3, $report['total_questions']);
        
        // Should have 1 wrong answer (question2 in most recent attempt)
        $this->assertCount(1, $report['wrong_answers']);
        $this->assertEquals('A', $report['wrong_answers'][0]['user_answer']);
        $this->assertEquals('B', $report['wrong_answers'][0]['correct_answer']);
    }

    public function test_nonexistent_student()
    {
        $reportService = new ReportService();
        $report = $reportService->generateDiagnosticReport('nonexistent');

        $this->assertArrayHasKey('error', $report);
        $this->assertStringContainsString('Student not found', $report['error']); // Fixed method name
    }

    public function test_incomplete_assessments_ignored()
    {
        $reportService = new ReportService();
        $report = $reportService->generateDiagnosticReport('student2');

        $this->assertArrayHasKey('error', $report);
        $this->assertStringContainsString('No completed assessments found', $report['error']); // Fixed method name
    }

    public function test_artisan_command()
    {
        $this->artisan('assessment:report', [
            '--student' => 'student1',
            '--report' => '1'
        ])
        ->expectsOutput('Assessment Reporting System')
        ->expectsOutput('==========================')
        ->assertExitCode(0);
    }

    public function test_artisan_command_with_invalid_student()
    {
        $this->artisan('assessment:report', [
            '--student' => 'invalid',
            '--report' => '1'
        ])
        ->expectsOutput('Student not found: invalid')
        ->assertExitCode(0);
    }
}
