<?php

namespace Tests\Unit\ProcessMaker\Support;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use ProcessMaker\Support\JsonDataPath;
use Tests\TestCase;

class JsonDataPathTest extends TestCase
{
    public static function validVariables(): array
    {
        return [
            ['amount'],
            ['var_amount'],
            ['grant.total'],
            ['a1.b_2'],
            ['x$'],
        ];
    }

    public static function invalidVariables(): array
    {
        return [
            'single quote' => ["amount'"],
            'double quote' => ['amount"'],
            'parenthesis' => ['amount)'],
            'backslash' => ['amount\\'],
            'space' => ['my amount'],
            'hyphen' => ['my-amount'],
            'leading digit' => ['1amount'],
            'empty segment' => ['a..b'],
            'trailing dot' => ['a.'],
            'empty string' => [''],
            'trailing newline' => ["amount\n"],
            'sql payload' => ["amount')) AS x, (SELECT 1) AS y, SUM(CAST(JSON_EXTRACT(data, '\$.amount"],
        ];
    }

    #[DataProvider('validVariables')]
    public function testValidVariablesAreAccepted(string $name): void
    {
        $this->assertTrue(JsonDataPath::isValid($name));
        $this->assertStringStartsWith('$."', JsonDataPath::fromDotNotation($name));
    }

    #[DataProvider('invalidVariables')]
    public function testInvalidVariablesAreRejected(string $name): void
    {
        $this->assertFalse(JsonDataPath::isValid($name));

        $this->expectException(InvalidArgumentException::class);
        JsonDataPath::fromDotNotation($name);
    }

    public function testNullIsInvalid(): void
    {
        $this->assertFalse(JsonDataPath::isValid(null));
    }

    public function testPathSegmentsAreQuoted(): void
    {
        $this->assertSame('$."amount"', JsonDataPath::fromDotNotation('amount'));
        $this->assertSame('$."grant"."total"', JsonDataPath::fromDotNotation('grant.total'));
    }

    public function testEmptySegmentListIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        JsonDataPath::fromSegments([]);
    }

    public function testFilterPatternAcceptsUnicodeSpacesAndHyphens(): void
    {
        $path = JsonDataPath::fromSegments(['montaña', 'año 2024', 'a-b'], JsonDataPath::FILTER_SEGMENT);

        $this->assertSame('$."montaña"."año 2024"."a-b"', $path);
    }

    public function testFilterPatternStillRejectsMetacharacters(): void
    {
        foreach (["a'b", 'a"b', 'a(b', 'a)b', 'a\\b'] as $segment) {
            $this->assertFalse(
                JsonDataPath::isValid($segment, JsonDataPath::FILTER_SEGMENT),
                "Segment {$segment} should be rejected"
            );
        }
    }

    public function testVariablePatternRejectsUnicode(): void
    {
        $this->assertFalse(JsonDataPath::isValid('montaña'));
    }
}
