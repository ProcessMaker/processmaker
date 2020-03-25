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
     * Test the use of string in FEEL
     *
     * "hello world"
     * 'hello world'
     */
    public function testStrings()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        //Double quoted string
        $expresion->setBody('"hello world"');
        $response = $expresion([]);
        $this->assertSame('hello world', $response);

        //Single quoted string
        $expresion->setBody("'hello world'");
        $response = $expresion([]);
        $this->assertSame('hello world', $response);
    }

    /**
     * Test the use of numbers in FEEL
     *
     * 122
     * 122.25
     * 12345678901234567889
     */
    public function testNumbers()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        //Evaluate an integer number
        $expresion->setBody('122');
        $response = $expresion([]);
        $this->assertSame(122, $response);

        //Evaluate an float number
        $expresion->setBody('122.25');
        $response = $expresion([]);
        $this->assertSame(122.25, $response);

        //Evaluate an large number
        $expresion->setBody('12345678901234567889');
        $response = $expresion([]);
        $this->assertSame(12345678901234567889, $response);
    }

    /**
     * Test the use of arrays in FEEL
     *
     * [0,2,3]
     */
    public function testArrays()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        //Evaluate an array
        $expresion->setBody('[0,2,3]');
        $response = $expresion([]);
        $this->assertSame([0, 2, 3], $response);
    }

    /**
     * Test the use of hashmaps in FEEL
     *
     * {a:33, "b": "foo"}
     * {complex: {a:[0,2,3], b:"bar"}}
     */
    public function testHashmaps()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        //Evaluate a hashmap
        $expresion->setBody('{a:33, "b": "foo"}');
        $response = $expresion([]);
        $this->assertSame(['a' => 33, 'b' => 'foo'], $response);

        //Evaluate a complex hashmap
        $expresion->setBody('{complex: {a:[0,2,3], b:"bar"}}');
        $response = $expresion([]);
        $this->assertSame(['complex' => ['a'=>[0, 2, 3], 'b' => 'bar']], $response);
    }

    /**
     * Test the use of booleans in FEEL
     *
     * true
     * false
     */
    public function testBooleans()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        //Evaluate a boolean
        $expresion->setBody('true');
        $response = $expresion([]);
        $this->assertSame(true, $response);

        //Evaluate a boolean
        $expresion->setBody('false');
        $response = $expresion([]);
        $this->assertSame(false, $response);
    }

    /**
     * Test arithmetic operations in FEEL
     *
     * 0 + 2 + 3 + 4
     * 0 * 2 + 3 * 4
     * 0 - 3 + 4 / 2
     */
    public function testArithmeticOperations()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        //Evaluate arithmetical expression
        $expresion->setBody('0 + 2 + 3 + 4');
        $response = $expresion([]);
        $this->assertSame(9, $response);

        //Evaluate arithmetical expression
        $expresion->setBody('0 * 2 + 3 * 4');
        $response = $expresion([]);
        $this->assertSame(13, $response);

        //Evaluate arithmetical expression
        $expresion->setBody('0 - 3 + 4 / 2');
        $response = $expresion([]);
        $this->assertSame(-1, $response);
    }

    /**
     * Test boolean expressions in FEEL
     *
     * true or false
     * true and false
     * true and not false
     */
    public function testBooleanExpressions()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        //Evaluate boolean expression
        $expresion->setBody('true or false');
        $response = $expresion([]);
        $this->assertSame(true, $response);

        //Evaluate boolean expression
        $expresion->setBody('true and false');
        $response = $expresion([]);
        $this->assertSame(false, $response);

        //Evaluate boolean expression
        $expresion->setBody('true and not false');
        $response = $expresion([]);
        $this->assertSame(true, $response);
    }

    /**
     * Test comparison expressions in FEEL
     *
     * 4 > 4
     * 4 <= 6
     * 4 != 6
     * 4 < 6
     * 4 >= 6
     * 4 == 6
     */
    public function testComparisonExpressions()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        //Evaluate comparison expression
        $expresion->setBody('4 > 4');
        $response = $expresion([]);
        $this->assertSame(true, $response);

        //Evaluate comparison expression
        $expresion->setBody('4 <= 6');
        $response = $expresion([]);
        $this->assertSame(true, $response);

        //Evaluate comparison expression
        $expresion->setBody('4 != 6');
        $response = $expresion([]);
        $this->assertSame(true, $response);

        //Evaluate comparison expression
        $expresion->setBody('4 < 6');
        $response = $expresion([]);
        $this->assertSame(true, $response);

        //Evaluate comparison expression
        $expresion->setBody('4 >= 6');
        $response = $expresion([]);
        $this->assertSame(false, $response);

        //Evaluate comparison expression
        $expresion->setBody('4 == 6');
        $response = $expresion([]);
        $this->assertSame(false, $response);
    }

    /**
     * Test string operations in FEEL
     *
     * "firstname" ~ " " ~ "lastname"
     * "firstname" matches "/first/"
     * "firstname" matches "/last/"
     */
    public function testStringOperations()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        //Evaluate string operation
        $expresion->setBody('"firstname" ~ " " ~ "lastname"');
        $response = $expresion([]);
        $this->assertSame('firstname lastname', $response);

        //Evaluate string operation
        $expresion->setBody('"firstname" matches "/first/"');
        $response = $expresion([]);
        $this->assertSame(0, $response);

        //Evaluate string operation
        $expresion->setBody('"firstname" matches "/last/"');
        $response = $expresion([]);
        $this->assertSame(-1, $response);
    }

    /**
     * Test array operations in FEEL
     *
     * 0 in [1,2,3]
     * 3 in [1,2,3]
     * 3 not in [1,2,3]
     */
    public function testArrayOperations()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        //Evaluate array operation
        $expresion->setBody('0 in [1,2,3]');
        $response = $expresion([]);
        $this->assertSame(true, $response);

        //Evaluate array operation
        $expresion->setBody('3 in [1,2,3]');
        $response = $expresion([]);
        $this->assertSame(false, $response);

        //Evaluate array operation
        $expresion->setBody('3 not in [1,2,3]');
        $response = $expresion([]);
        $this->assertSame(true, $response);
    }

    /**
     * Test ternary operator in FEEL
     *
     * true ? "true" : "false"
     * false ? "true" : "false"
     */
    public function testTernaryOperator()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        //Evaluate ternary operator
        $expresion->setBody('true ? "true" : "false"');
        $response = $expresion([]);
        $this->assertSame('true', $response);

        //Evaluate ternary operator
        $expresion->setBody('false ? "true" : "false"');
        $response = $expresion([]);
        $this->assertSame('false', $response);
    }

    /**
     * Test access to data
     *
     * input
     * foo.property
     * {a: input, b: foo.name}
     */
    public function testAccessToData()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');
        $data = [
            'foo' => [
                'property' => 122,
                'name' => 'name',
            ],
            'input' => 'bar',
        ];

        //Evaluate expression
        $expresion->setBody('input');
        $response = $expresion($data);
        $this->assertSame($data['input'], $response);

        //Evaluate expression
        $expresion->setBody('foo.property');
        $response = $expresion($data);
        $this->assertSame($data['foo']['property'], $response);

        //Evaluate expression
        $expresion->setBody('{a: input, b: foo.name}');
        $response = $expresion($data);
        $this->assertEquals(['a' => $data['input'], 'b' => $data['foo']['name']], $response);
    }

    /**
     * Test syntax error exception
     *
     * input +
     */
    public function testSyntaxErrorException()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');

        //SyntaxErrorException expected
        $this->expectException(SyntaxErrorException::class);
        $expresion->setBody('input +');
        $response = $expresion([]);
    }

    /**
     * Test that expression fails execution
     *
     * nonobject.bar
     */
    public function testExpressionFailedException()
    {
        $expresion = new FormalExpression();
        $expresion->setLanguage('FEEL');
        $data = [
            'nonobject'  => 'bar',
        ];

        //ExpressionFailedException expected
        $this->expectException(ExpressionFailedException::class);
        $expresion->setBody('{a: nonobject.bar}');
        $response = $expresion($data);
        $this->assertSame(true, $response);
    }
}
