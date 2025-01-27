<?php

namespace Tests\Model;

use ProcessMaker\Exception\ScriptLanguageNotSupported;
use ProcessMaker\Models\FormalExpression;
use Tests\TestCase;

final class FormalExpressionTest extends TestCase
{
    public function testEvaluateSimpleExpression(): void
    {
        $formalExp = new FormalExpression();
        $formalExp->setLanguage('FEEL');
        $formalExp->setBody('condition == "passed"');
        $eval = $formalExp(['condition' => 'test']);
        $this->assertFalse($eval);

        $eval = $formalExp(['condition' => 'passed']);
        $this->assertTrue($eval);
    }

    public function testLanguageNotSupported(): void
    {
        $this->expectException(ScriptLanguageNotSupported::class);

        $formalExp = new FormalExpression();
        $formalExp->setLanguage('FEEL-X');
        $formalExp->setBody('condition == "passed"');
        $eval = $formalExp(['condition' => 'test']);
    }

    public function testEvaluateExpressionWithMustache(): void
    {
        $formalExp = new FormalExpression();
        $formalExp->setLanguage('FEEL');
        $formalExp->setBody('{{{expression}}}');
        $eval = $formalExp(['expression' => 'condition == "passed"', 'condition' => 'test']);
        $this->assertFalse($eval);

        $eval = $formalExp(['expression' => "condition == 'passed'", 'condition' => 'passed']);
        $this->assertTrue($eval);
    }
}
