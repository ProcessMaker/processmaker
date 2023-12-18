<?php

namespace Tests;

use ProcessMaker\Models\ProcessRequest;

class CaseTitleTest extends TestCase
{
    const MUSTACHE_VARIABLE = '{{name}}';

    public function testEvaluateCaseTitleWithoutFormatting()
    {
        $processRequest = new ProcessRequest();
        $title = $processRequest->evaluateCaseTitle('Hello, {{name}}!', ['name' => 'World'], false);

        $this->assertEquals('Hello, World!', $title);
    }

    public function testEvaluateCaseTitleWithFormatting()
    {
        $processRequest = new ProcessRequest();
        $title = $processRequest->evaluateCaseTitle('Hello, {{name}}!', ['name' => 'World'], true);

        $this->assertEquals('Hello, <b>World</b>!', $title);
    }

    public function testEvaluateCaseTitleWithCharacterLimit()
    {
        $processRequest = new ProcessRequest();
        $longString = str_repeat('a', 300);
        $title = $processRequest->evaluateCaseTitle($longString, [], true);

        $this->assertEquals(200, mb_strlen($title));
    }

    public function testEvaluateCaseTitleWithHtmlTagsNotCountedInCharacterLimit()
    {
        $processRequest = new ProcessRequest();
        $longString = str_repeat('a', 195) . self::MUSTACHE_VARIABLE;
        $title = $processRequest->evaluateCaseTitle($longString, ['name' => 'World'], true);

        $this->assertEquals(200 + 7, mb_strlen($title)); // 7 is the length of '<b></b>'
        $this->assertEquals(str_repeat('a', 195) . '<b>World</b>', $title);
    }

    public function testEvaluateCaseTitleWithHtmlTagsNotCountedTwoCharactersMoreThanLimit()
    {
        $processRequest = new ProcessRequest();
        $longString = str_repeat('a', 197) . self::MUSTACHE_VARIABLE;
        $title = $processRequest->evaluateCaseTitle($longString, ['name' => 'World'], true);

        $this->assertEquals(200 + 7, mb_strlen($title)); // 7 is the length of '<b></b>'
        $this->assertEquals(str_repeat('a', 197) . '<b>Wor</b>', $title);
    }

    public function testEvaluateCaseTitleWithoutHtmlTagsThatExceededTheLimit()
    {
        $processRequest = new ProcessRequest();
        $longString = str_repeat('a', 200) . self::MUSTACHE_VARIABLE;
        $title = $processRequest->evaluateCaseTitle($longString, ['name' => 'World'], true);

        $this->assertEquals(200, mb_strlen($title));
        $this->assertEquals(str_repeat('a', 200), $title);
    }
}
