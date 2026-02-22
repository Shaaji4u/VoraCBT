<?php

declare(strict_types=1);

namespace Tests\Domain\Grading;

use App\Domain\Grading\Service\GradingStrategy\FillInTheBlankGradingStrategy;
use App\Domain\Grading\Service\GradingStrategy\MatchingGradingStrategy;
use App\Domain\Grading\Service\GradingStrategy\MCQGradingStrategy;
use App\Domain\Grading\Service\GradingStrategy\NumericalGradingStrategy;
use PHPUnit\Framework\TestCase;

class GradingStrategiesTest extends TestCase
{
    // --- MCQ Strategy ---

    public function testMCQExactMatch(): void
    {
        $strategy = new MCQGradingStrategy();
        $question = ['correct_options' => ['opt1', 'opt2']];
        $answer = ['selected_options' => ['opt1', 'opt2']];

        $result = $strategy->grade($question, $answer, 10.0);

        $this->assertTrue($result->isCorrect);
        $this->assertEquals(10.0, $result->marksObtained);
    }

    public function testMCQIncorrect(): void
    {
        $strategy = new MCQGradingStrategy();
        $question = ['correct_options' => ['opt1']];
        $answer = ['selected_options' => ['opt2']];

        $result = $strategy->grade($question, $answer, 5.0);

        $this->assertFalse($result->isCorrect);
        $this->assertEquals(0.0, $result->marksObtained);
    }

    public function testMCQPartialScoring(): void
    {
        $strategy = new MCQGradingStrategy();
        $question = ['correct_options' => ['opt1', 'opt2', 'opt3', 'opt4']];

        // Case 1: 2 correct, 0 wrong (Partial)
        $answer1 = ['selected_options' => ['opt1', 'opt2']];
        $result1 = $strategy->grade($question, $answer1, 10.0, ['partial_scoring' => true]);

        // (2 - 0) / 4 * 10 = 5.0
        $this->assertEquals(5.0, $result1->marksObtained);
        $this->assertFalse($result1->isCorrect); // Partial is not fully correct

        // Case 2: 3 correct, 1 wrong
        $answer2 = ['selected_options' => ['opt1', 'opt2', 'opt3', 'wrong1']];
        $result2 = $strategy->grade($question, $answer2, 10.0, ['partial_scoring' => true]);

        // (3 - 1) / 4 * 10 = 2/4 * 10 = 5.0
        $this->assertEquals(5.0, $result2->marksObtained);

        // Case 3: All correct (Exact via partial logic)
        $answer3 = ['selected_options' => ['opt1', 'opt2', 'opt3', 'opt4']];
        // Strategy returns Exact match logic if all match, even if partial enabled?
        // Let's check code: Yes, if exact match, returns full marks.
        $result3 = $strategy->grade($question, $answer3, 10.0, ['partial_scoring' => true]);
        $this->assertEquals(10.0, $result3->marksObtained);
        $this->assertTrue($result3->isCorrect);
    }

    // --- Fill-in-the-Blank Strategy ---

    public function testFillInTheBlankExact(): void
    {
        $strategy = new FillInTheBlankGradingStrategy();
        $question = ['blanks' => ['blank1' => 'answer1', 'blank2' => 'Answer2']];

        // Case insensitive match
        $answer = ['answers' => ['blank1' => 'ANSWER1', 'blank2' => 'answer2']];
        $result = $strategy->grade($question, $answer, 10.0);

        $this->assertEquals(10.0, $result->marksObtained);
        $this->assertTrue($result->isCorrect);
    }

    public function testFillInTheBlankRegex(): void
    {
        $strategy = new FillInTheBlankGradingStrategy();
        $question = ['blanks' => [
            'b1' => ['regex' => '/^\d{4}$/', 'answer' => '2024']
        ]];

        $answer = ['answers' => ['b1' => '2023']]; // Matches regex
        $result = $strategy->grade($question, $answer, 5.0);

        $this->assertEquals(5.0, $result->marksObtained);
        $this->assertTrue($result->isCorrect);

        $answerFail = ['answers' => ['b1' => 'abc']];
        $resultFail = $strategy->grade($question, $answerFail, 5.0);
        $this->assertEquals(0.0, $resultFail->marksObtained);
    }

    // --- Numerical Strategy ---

    public function testNumericalTolerance(): void
    {
        $strategy = new NumericalGradingStrategy();
        $question = ['answer' => 10.0, 'tolerance' => 0.5];

        $result1 = $strategy->grade($question, ['value' => 10.2], 5.0);
        $this->assertEquals(5.0, $result1->marksObtained);

        $result2 = $strategy->grade($question, ['value' => 9.5], 5.0); // Exact boundary
        $this->assertEquals(5.0, $result2->marksObtained);

        $result3 = $strategy->grade($question, ['value' => 10.6], 5.0);
        $this->assertEquals(0.0, $result3->marksObtained);
    }

    // --- Matching Strategy ---

    public function testMatching(): void
    {
        $strategy = new MatchingGradingStrategy();
        $question = ['pairs' => [
            ['left' => 'A', 'right' => '1'],
            ['left' => 'B', 'right' => '2']
        ]];

        // 1 correct, 1 wrong
        $answer = ['pairs' => [
            ['left' => 'A', 'right' => '1'],
            ['left' => 'B', 'right' => '3']
        ]];

        $result = $strategy->grade($question, $answer, 10.0);

        // 1/2 correct = 5.0
        $this->assertEquals(5.0, $result->marksObtained);
        $this->assertFalse($result->isCorrect);
    }
}
