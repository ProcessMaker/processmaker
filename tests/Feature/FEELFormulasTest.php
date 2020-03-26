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
class FEELFormulasTest extends TestCase
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
     * Test formulas
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

    /**
     * Test formulas with strings concatenation
     */
    public function testConcatenatedFormula()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        // When calculation is true
        $expresion->setBody("Height == 'cm: ' ~ (2 * Weight)");
        $response = $expresion( [ "Height" => "cm: 120", "Weight" => 60]);
        $this->assertSame(true, $response);

        // When calculation is false
        $expresion->setBody("Height == 'cm: ' ~ (2 * Weight)");
        $response = $expresion( [ "Height" => "km: 120", "Weight" => 60]);
        $this->assertSame(false, $response);
    }
}
