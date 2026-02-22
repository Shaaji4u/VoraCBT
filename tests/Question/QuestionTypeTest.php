<?php

declare(strict_types=1);

namespace Tests\Question;

use App\Question\Type\MCQType;
use App\Question\Type\FillInTheBlankType;
use App\Question\Type\EssayType;
use App\Question\Type\NumericalType;
use App\Question\Type\MatchingType;
use App\Question\Type\PassageType;
use App\Question\Type\ImageBasedType;
use App\Question\Type\QuestionTypeFactory;
use App\Question\Exception\QuestionValidationException;
use PHPUnit\Framework\TestCase;

class QuestionTypeTest extends TestCase
{
    public function testMCQValidation(): void
    {
        $type = new MCQType();
        $this->assertEquals('mcq', $type->getTypeName());

        $validPayload = [
            'prompt' => 'What is 1+1?',
            'options' => [
                ['id' => '1', 'text' => '2'],
                ['id' => '2', 'text' => '3']
            ],
            'correct_options' => ['1']
        ];
        $type->validate($validPayload);
        $this->assertTrue(true); // Should not throw

        $this->expectException(QuestionValidationException::class);
        $type->validate(['prompt' => 'Missing options']);
    }

    public function testMCQValidationMissingIds(): void
    {
        $type = new MCQType();
        $invalidPayload = [
            'prompt' => 'What is 1+1?',
            'options' => [
                ['text' => '2'], // Missing ID
            ],
            'correct_options' => ['1']
        ];
        $this->expectException(QuestionValidationException::class);
        $type->validate($invalidPayload);
    }

    public function testMCQNormalization(): void
    {
        $type = new MCQType();
        $payload = [
            'prompt' => 'Q',
            'options' => [['id' => '1', 'text' => 'A'], ['id' => '2', 'text' => 'B']],
            'correct_options' => ['1']
        ];
        $normalized = $type->normalize($payload);

        $this->assertFalse($normalized['randomize_options']);
    }

    public function testFillInTheBlankValidation(): void
    {
        $type = new FillInTheBlankType();
        $validPayload = [
            'text' => 'The capital of France is [[1]].',
            'blanks' => [
                '1' => ['correct' => ['Paris']]
            ]
        ];
        $type->validate($validPayload);
        $this->assertTrue(true);

        $this->expectException(QuestionValidationException::class);
        $type->validate(['text' => 'No blanks', 'blanks' => []]);
    }

    public function testEssayValidation(): void
    {
        $type = new EssayType();
        $validPayload = ['text' => 'Write an essay.'];
        $type->validate($validPayload);
        $this->assertTrue(true);

        $this->expectException(QuestionValidationException::class);
        $type->validate(['min_words' => 100, 'max_words' => 50, 'text' => 'Invalid range']);
    }

    public function testNumericalValidation(): void
    {
        $type = new NumericalType();
        $validPayload = ['text' => '1+1?', 'answer' => 2];
        $type->validate($validPayload);
        $this->assertTrue(true);

        $this->expectException(QuestionValidationException::class);
        $type->validate(['text' => '1+1?', 'answer' => 'two']);
    }

    public function testMatchingValidation(): void
    {
        $type = new MatchingType();
        $validPayload = [
            'text' => 'Match items',
            'pairs' => [
                ['left' => 'A', 'right' => '1'],
                ['left' => 'B', 'right' => '2']
            ]
        ];
        $type->validate($validPayload);
        $this->assertTrue(true);

        $this->expectException(QuestionValidationException::class);
        $type->validate(['text' => 'Match', 'pairs' => []]);
    }

    public function testPassageValidation(): void
    {
        $type = new PassageType();
        $validPayload = [
            'passage' => 'Read this.',
            'questions' => [
                [
                    'type' => 'mcq',
                    'content' => [
                        'prompt' => 'Q1',
                        'options' => [['id' => '1', 'text' => 'A']],
                        'correct_options' => ['1']
                    ]
                ]
            ]
        ];
        $type->validate($validPayload);
        $this->assertTrue(true);

        $this->expectException(QuestionValidationException::class);
        $type->validate(['passage' => 'Read', 'questions' => 'not array']);
    }

    public function testPassageRecursiveValidationFailure(): void
    {
        $type = new PassageType();
        $invalidPayload = [
            'passage' => 'Read this.',
            'questions' => [
                [
                    'type' => 'mcq',
                    'content' => [
                        'prompt' => 'Q1',
                        'options' => [['text' => 'A']], // Missing ID
                        'correct_options' => ['1']
                    ]
                ]
            ]
        ];
        $this->expectException(QuestionValidationException::class);
        $type->validate($invalidPayload);
    }

    public function testImageBasedValidation(): void
    {
        $type = new ImageBasedType();
        $validPayload = ['image_url' => 'http://example.com/img.jpg', 'prompt' => 'Describe'];
        $type->validate($validPayload);
        $this->assertTrue(true);

        $this->expectException(QuestionValidationException::class);
        $type->validate(['prompt' => 'Missing image']);
    }

    public function testFactory(): void
    {
        $mcq = QuestionTypeFactory::create('mcq');
        $this->assertInstanceOf(MCQType::class, $mcq);

        $this->expectException(\App\Question\Exception\QuestionException::class);
        QuestionTypeFactory::create('invalid_type');
    }
}
