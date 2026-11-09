<?php

namespace JundFauz\FilamentKompact\Tests\Unit;

use JundFauz\FilamentKompact\Tests\TestCase;
use JundFauz\FilamentKompact\UI\Formatter\Format;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class FormatTest extends TestCase
{
    public static function blankValues(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'whitespace' => ['   '],
        ];
    }

    #[Test]
    #[DataProvider('blankValues')]
    public function every_formatter_returns_a_dash_for_a_blank_value(mixed $value): void
    {
        $this->assertSame('-', Format::gram($value));
        $this->assertSame('-', Format::pcs($value));
        $this->assertSame('-', Format::percentage($value));
        $this->assertSame('-', Format::carat($value));
        $this->assertSame('-', Format::time($value));
    }

    #[Test]
    public function gram_has_three_decimals_rounded_up(): void
    {
        $this->assertSame('12.000 gr', Format::gram(12));
        $this->assertSame('1.235 gr', Format::gram('1.2341'));
        $this->assertSame('0.500 gr', Format::gram(0.5));
    }

    #[Test]
    public function gram_keeps_zero(): void
    {
        $this->assertSame('0.000 gr', Format::gram(0));
        $this->assertSame('0.000 gr', Format::gram('0'));
    }

    #[Test]
    public function pcs_is_an_integer_with_thousand_separators(): void
    {
        $this->assertSame('1,234,567 pcs', Format::pcs(1234567));
        $this->assertSame('12 pcs', Format::pcs('12.9'));
        $this->assertSame('0 pcs', Format::pcs(0));
    }

    #[Test]
    public function percentage_and_carat_append_their_unit(): void
    {
        $this->assertSame('12.5%', Format::percentage(12.5));
        $this->assertSame('24 K', Format::carat(24));
    }

    #[Test]
    public function time_keeps_hours_and_minutes(): void
    {
        $this->assertSame('08:30', Format::time('08:30:59'));
        $this->assertSame('08:30', Format::time('08:30'));
    }
}
