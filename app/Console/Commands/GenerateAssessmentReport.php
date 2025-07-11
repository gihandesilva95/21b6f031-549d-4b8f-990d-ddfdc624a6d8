<?php

// app/Console/Commands/GenerateAssessmentReport.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportService;

class GenerateAssessmentReport extends Command
{
    protected $signature = 'assessment:report {--student= : Student ID} {--report= : Report type (1-3)}';
    protected $description = 'Generate assessment reports for students';

    private $reportService;

    public function __construct(ReportService $reportService)
    {
        parent::__construct();
        $this->reportService = $reportService;
    }

    public function handle()
    {
        $this->info('Assessment Reporting System');
        $this->info('==========================');
        
        // Get user input
        $studentId = $this->option('student') ?: $this->ask('Student ID');
        
        if ($this->option('report')) {
            $reportNumber = (int) $this->option('report');
        } else {
            $reportChoice = $this->choice(
                'Report to generate',
                ['1 - Diagnostic', '2 - Progress', '3 - Feedback']
            );
            $reportNumber = (int) substr($reportChoice, 0, 1);
        }
        
        // Generate report
        switch ($reportNumber) {
            case 1:
                $this->displayDiagnosticReport($studentId);
                break;
            case 2:
                $this->displayProgressReport($studentId);
                break;
            case 3:
                $this->displayFeedbackReport($studentId);
                break;
            default:
                $this->error('Invalid report type. Please choose 1, 2, or 3.');
        }
    }

    private function displayDiagnosticReport($studentId)
    {
        $report = $this->reportService->generateDiagnosticReport($studentId);
        
        if (isset($report['error'])) {
            $this->error($report['error']);
            return;
        }

        $student = $report['student'];
        $assessment = $report['assessment'];
        
        $this->line('');
        $this->info("{$student['firstName']} {$student['lastName']} recently completed {$assessment['name']} assessment on {$report['completed_date']}");
        $this->info("He got {$report['total_correct']} questions right out of {$report['total_questions']}. Details by strand given below:");
        $this->line('');

        foreach ($report['strand_scores'] as $strand => $scores) {
            $this->info("{$strand}: {$scores['correct']} out of {$scores['total']} correct");
        }
    }

    private function displayProgressReport($studentId)
    {
        $report = $this->reportService->generateProgressReport($studentId);
        
        if (isset($report['error'])) {
            $this->error($report['error']);
            return;
        }

        $student = $report['student'];
        
        $this->line('');
        $this->info("{$student['firstName']} {$student['lastName']} has completed {$report['assessment_name']} assessment {$report['total_completions']} times in total. Date and raw score given below:");
        $this->line('');

        foreach ($report['attempts'] as $attempt) {
            $this->info("Date: {$attempt['date']}, Raw Score: {$attempt['score']} out of {$attempt['total']}");
        }

        $this->line('');
        $this->info("{$student['firstName']} {$student['lastName']} got {$report['improvement']} more correct in the recent completed assessment than the oldest");
    }

    private function displayFeedbackReport($studentId)
    {
        $report = $this->reportService->generateFeedbackReport($studentId);
        
        if (isset($report['error'])) {
            $this->error($report['error']);
            return;
        }

        $student = $report['student'];
        $assessment = $report['assessment'];
        
        $this->line('');
        $this->info("{$student['firstName']} {$student['lastName']} recently completed {$assessment['name']} assessment on {$report['completed_date']}");
        $this->info("He got {$report['total_correct']} questions right out of {$report['total_questions']}. Feedback for wrong answers given below");
        $this->line('');

        if (empty($report['wrong_answers'])) {
            $this->info("All answers were correct!");
            return;
        }

        foreach ($report['wrong_answers'] as $wrong) {
            $this->info("Question: {$wrong['question']}");
            $this->info("Your answer: {$wrong['user_answer']} with value {$wrong['user_answer_value']}");
            $this->info("Right answer: {$wrong['correct_answer']} with value {$wrong['correct_answer_value']}");
            $this->info("Hint: {$wrong['hint']}");
            $this->line('');
        }
    }
}