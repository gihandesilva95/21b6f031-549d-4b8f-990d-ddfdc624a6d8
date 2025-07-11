<?php

// app/Services/ReportService.php
namespace App\Services;

use Illuminate\Support\Collection;
use Carbon\Carbon;

class ReportService
{
    private $students;
    private $assessments;
    private $questions;
    private $responses;

    public function __construct()
    {
        $this->loadData();
    }

    private function loadData()
    {
        $this->students = $this->loadJsonFile('students.json');
        $this->assessments = $this->loadJsonFile('assessments.json');
        $this->questions = $this->loadJsonFile('questions.json');
        $this->responses = $this->loadJsonFile('student-responses.json');
    }

    private function loadJsonFile($filename)
    {
        $path = storage_path("app/data/{$filename}");
        if (!file_exists($path)) {
            return collect([]);
        }
        return collect(json_decode(file_get_contents($path), true));
    }

    public function generateDiagnosticReport($studentId)
    {
        $student = $this->findStudent($studentId);
        if (!$student) {
            return ['error' => "Student not found: $studentId"];
        }

        $recentResponse = $this->getMostRecentCompletedAssessment($studentId);
        if (!$recentResponse) {
            return ['error' => "No completed assessments found for student: $studentId"];
        }

        $assessment = $this->findAssessment($recentResponse['assessmentId']);
        $completedDate = $this->parseDate($recentResponse['completed']);
        
        $strandScores = $this->calculateStrandScores($recentResponse);
        $totalCorrect = array_sum(array_column($strandScores, 'correct'));
        $totalQuestions = array_sum(array_column($strandScores, 'total'));

        return [
            'student' => $student,
            'assessment' => $assessment,
            'completed_date' => $completedDate,
            'total_correct' => $totalCorrect,
            'total_questions' => $totalQuestions,
            'strand_scores' => $strandScores
        ];
    }

    public function generateProgressReport($studentId)
    {
        $student = $this->findStudent($studentId);
        if (!$student) {
            return ['error' => "Student not found: $studentId"];
        }

        $completedAssessments = $this->getCompletedAssessments($studentId);
        if (empty($completedAssessments)) {
            return ['error' => "No completed assessments found for student: $studentId"];
        }

        $assessmentName = $this->findAssessment($completedAssessments[0]['assessmentId'])['name'];
        $totalCompletions = count($completedAssessments);

        $attempts = [];
        $scores = [];
        
        foreach ($completedAssessments as $response) {
            $date = $this->parseDate($response['completed']);
            $score = $response['results']['rawScore'];
            $totalQuestions = count($response['responses']);
            
            $attempts[] = [
                'date' => $date,
                'score' => $score,
                'total' => $totalQuestions
            ];
            $scores[] = $score;
        }

        $improvement = end($scores) - $scores[0];

        return [
            'student' => $student,
            'assessment_name' => $assessmentName,
            'total_completions' => $totalCompletions,
            'attempts' => $attempts,
            'improvement' => $improvement
        ];
    }

    public function generateFeedbackReport($studentId)
    {
        $student = $this->findStudent($studentId);
        if (!$student) {
            return ['error' => "Student not found: $studentId"];
        }

        $recentResponse = $this->getMostRecentCompletedAssessment($studentId);
        if (!$recentResponse) {
            return ['error' => "No completed assessments found for student: $studentId"];
        }

        $assessment = $this->findAssessment($recentResponse['assessmentId']);
        $completedDate = $this->parseDate($recentResponse['completed']);
        
        $totalCorrect = $recentResponse['results']['rawScore'];
        $totalQuestions = count($recentResponse['responses']);

        $wrongAnswers = [];
        foreach ($recentResponse['responses'] as $response) {
            $question = $this->findQuestion($response['questionId']);
            if ($response['response'] !== $question['config']['key']) {
                $userOption = $this->findOptionById($question, $response['response']);
                $correctOption = $this->findOptionById($question, $question['config']['key']);
                
                $wrongAnswers[] = [
                    'question' => $question['stem'],
                    'user_answer' => $userOption['label'],
                    'user_answer_value' => $userOption['value'],
                    'correct_answer' => $correctOption['label'],
                    'correct_answer_value' => $correctOption['value'],
                    'hint' => $question['config']['hint']
                ];
            }
        }

        return [
            'student' => $student,
            'assessment' => $assessment,
            'completed_date' => $completedDate,
            'total_correct' => $totalCorrect,
            'total_questions' => $totalQuestions,
            'wrong_answers' => $wrongAnswers
        ];
    }

    private function findStudent($studentId)
    {
        return $this->students->firstWhere('id', $studentId);
    }

    private function findAssessment($assessmentId)
    {
        return $this->assessments->firstWhere('id', $assessmentId);
    }

    private function findQuestion($questionId)
    {
        return $this->questions->firstWhere('id', $questionId);
    }

    private function findOptionById($question, $optionId)
    {
        return collect($question['config']['options'])->firstWhere('id', $optionId);
    }

    private function getMostRecentCompletedAssessment($studentId)
    {
        return $this->responses
            ->where('student.id', $studentId)
            ->whereNotNull('completed')
            ->sortByDesc(function($response) {
                return $this->parseDate($response['completed'], false);
            })
            ->first();
    }

    private function getCompletedAssessments($studentId)
    {
        return $this->responses
            ->where('student.id', $studentId)
            ->whereNotNull('completed')
            ->sortBy(function($response) {
                return $this->parseDate($response['completed'], false);
            })
            ->values()
            ->all();
    }

    private function calculateStrandScores($response)
    {
        $strandScores = [];
        
        foreach ($response['responses'] as $resp) {
            $question = $this->findQuestion($resp['questionId']);
            $strand = $question['strand'];
            
            if (!isset($strandScores[$strand])) {
                $strandScores[$strand] = ['correct' => 0, 'total' => 0];
            }
            
            $strandScores[$strand]['total']++;
            if ($resp['response'] === $question['config']['key']) {
                $strandScores[$strand]['correct']++;
            }
        }
        
        return $strandScores;
    }

    private function parseDate($dateString, $format = true)
    {
        // Handle the date format: "16/12/2019 10:46:00"
        $carbon = Carbon::createFromFormat('d/m/Y H:i:s', $dateString);
        
        if ($format) {
            return $carbon->format('jS F Y g:i A');
        }
        
        return $carbon;
    }
}