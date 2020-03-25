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

    /**
     * Test formulas
     * Weight = 2 * Height
     */
    public function testCalculatedFields()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        // When calculation is true
        $expresion->setBody("Height == 2 * Weight");
        $response = $expresion( [ "Height" => 120, "Weight" => 60]);
        $this->assertSame(true, $response);

        // When calculation is false
        $expresion->setBody("Height == 1 * Weight");
        $response = $expresion( [ "Height" => 120, "Weight" => 60]);
        $this->assertSame(false, $response);
    }

}
