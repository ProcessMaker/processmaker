<?php
namespace Tests\Feature;

use ProcessMaker\Exception\ExpressionFailedException;
use ProcessMaker\Exception\ScriptLanguageNotSupported;
use ProcessMaker\Exception\SyntaxErrorException;
use ProcessMaker\Models\FormalExpression;
use Tests\TestCase;

/**
 * Test friendly enough expression language evaluator working together with mustache expressions
 */
class MustacheFEELTest extends TestCase
{

    /**
     * Test to use an unsupported language
     */
    public function testUnsupportedLanguage()
    {
        $this->expectException(ScriptLanguageNotSupported::class);
        $expresion = new FormalExpression();
        $expresion->setLanguage('application/x-unsupported');
        $expresion->setBody('"hello world"');
        $response = $expresion([]);
    }

    /**
     * Test the use of strings
     *
     * "hello world"
     * 'hello world'
     */
    public function testStrings()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        //Single quoted string
        $expresion->setBody("'hello world'");
        $response = $expresion([]);
        $this->assertSame('hello world', $response);
    }

}
