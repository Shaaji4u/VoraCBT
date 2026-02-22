<?php

declare(strict_types=1);

namespace Tests\Domain\Identity;

use App\Domain\Identity\PasswordGenerator;
use PHPUnit\Framework\TestCase;

class PasswordGeneratorTest extends TestCase
{
    private PasswordGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new PasswordGenerator();
    }

    public function testGenerateDefaultLength(): void
    {
        $password = $this->generator->generate();
        $this->assertEquals(10, strlen($password));
    }

    public function testGenerateCustomLength(): void
    {
        $password = $this->generator->generate(12);
        $this->assertEquals(12, strlen($password));
    }

    public function testGenerateMinimumLength(): void
    {
        $password = $this->generator->generate(5); // Should default to 8
        $this->assertGreaterThanOrEqual(8, strlen($password));
    }

    public function testPasswordRequirements(): void
    {
        $password = $this->generator->generate();

        $this->assertMatchesRegularExpression('/[A-Z]/', $password, 'Password must contain uppercase');
        $this->assertMatchesRegularExpression('/[a-z]/', $password, 'Password must contain lowercase');
        $this->assertMatchesRegularExpression('/[0-9]/', $password, 'Password must contain number');
    }

    public function testNoAmbiguousChars(): void
    {
        $password = $this->generator->generate(50); // Generate a long one to check

        $this->assertStringNotContainsString('I', $password);
        $this->assertStringNotContainsString('O', $password);
        $this->assertStringNotContainsString('l', $password);
        $this->assertStringNotContainsString('0', $password);
        $this->assertStringNotContainsString('1', $password);
    }
}
