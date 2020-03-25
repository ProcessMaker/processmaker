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
     * Test formulas with mustache
     */
    public function testFormulaAndMustache()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        // When calculation is true
        $expresion->setBody("Height == '{{Units}}: ' ~ (2 * Weight)");
        $response = $expresion( [ "Height" => "cm: 120", "Weight" => 60, "Units" => "cm"]);
        $this->assertSame(true, $response);

        // When calculation is false
        $expresion->setBody("Height == '{{Units}}: ' ~ (2 * Weight)");
        $response = $expresion( [ "Height" => "cm: 120", "Weight" => 60, "Units" => "km"]);
        $this->assertSame(false, $response);
    }

}
