<?php

declare(strict_types=1);

namespace App\Question\Type;

use App\Question\Exception\QuestionException;

class QuestionTypeFactory
{
    private static array $types = [
        'mcq' => MCQType::class,
        'fill_in_the_blank' => FillInTheBlankType::class,
        'essay' => EssayType::class,
        'numerical' => NumericalType::class,
        'matching' => MatchingType::class,
        'passage' => PassageType::class,
        'image_based' => ImageBasedType::class,
    ];

    public static function create(string $type): QuestionTypeInterface
    {
        if (!array_key_exists($type, self::$types)) {
            throw new QuestionException("Unsupported question type: $type");
        }

        $class = self::$types[$type];
        return new $class();
    }

    public static function getSupportedTypes(): array
    {
        return array_keys(self::$types);
    }
}
