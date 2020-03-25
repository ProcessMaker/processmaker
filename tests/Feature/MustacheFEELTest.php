<?php
namespace Tests\Feature;

use ProcessMaker\Exception\ExpressionFailedException;
use ProcessMaker\Exception\ScriptLanguageNotSupported;
use ProcessMaker\Exception\SyntaxErrorException;
use ProcessMaker\Models\FormalExpression;
use Tests\TestCase;

/**
 * Test FELL expressions that uses Mustache syntax
 */
class MustacheFEELTest extends TestCase
{
    /**
     * Test basic mustache templating
     */
    public function testMustacheTemplates()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        $expresion->setBody("{{Value}}");
        $response = $expresion(["Value" => 11]);
        $this->assertSame(11, $response);
    }

    /**
     * Test basic mustache with FEEL string concatenation
     */
    public function testStringConcatenation()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        $expresion->setBody("{{Value}}~'Test'");
        $response = $expresion(["Value" => 11]);
        $this->assertSame("11Test", $response);

        $expresion->setBody("'{{Value}}'~'{{Other}}'");
        $response = $expresion(["Value" => 11, "Other" => 22]);
        $this->assertSame("1122", $response);
    }

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
