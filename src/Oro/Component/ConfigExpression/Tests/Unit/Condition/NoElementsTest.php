<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\ConfigExpression\Condition\NoElements;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class NoElementsTest extends \PHPUnit_Framework_TestCase
{
    /** @var NoElements */
    protected $condition;

    protected function setUp()
    {
        $this->condition = new NoElements();
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    /**
     * @dataProvider evaluateDataProvider
     * @param array $options
     * @param array $context
     * @param bool $expectedResult
     */
    public function testEvaluate(array $options, array $context, $expectedResult)
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function evaluateDataProvider()
    {
        return [
            'not_empty_array' => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => ['bar']],
                'expectedResult' => false
            ],
            'empty_array' => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => []],
                'expectedResult' => true
            ],
            'not empty_collection' => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => new ArrayCollection([new \stdClass()])],
                'expectedResult' => false
            ],
            'empty_collection' => [
                'options'        => [new PropertyPath('foo')],
                'context'        => ['foo' => new ArrayCollection()],
                'expectedResult' => true
            ]
        ];
    }

    public function testAddError()
    {
        $context = ['foo' => ['bar']];
        $options = [new PropertyPath('foo')];

        $this->condition->initialize($options);
        $message = 'Error message.';
        $this->condition->setMessage($message);

        $errors = new ArrayCollection();

        $this->assertFalse($this->condition->evaluate($context, $errors));

        $this->assertCount(1, $errors);
        $this->assertEquals(
            ['message' => $message, 'parameters' => ['{{ value }}' => ['bar']]],
            $errors->get(0)
        );
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have 1 element, but 0 given.
     */
    public function testInitializeFailsWithEmptyOptions()
    {
        $this->condition->initialize([]);
    }

    /**
     * @dataProvider toArrayDataProvider
     * @param array $options
     * @param string|null $message
     * @param array $expected
     */
    public function testToArray(array $options, $message, array $expected)
    {
        $this->condition->initialize($options);
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function toArrayDataProvider()
    {
        return [
            [
                'options'  => ['value'],
                'message'  => null,
                'expected' => [
                    '@no_elements' => [
                        'parameters' => [
                            'value'
                        ]
                    ]
                ]
            ],
            [
                'options'  => ['value'],
                'message'  => 'Test message',
                'expected' => [
                    '@no_elements' => [
                        'message'    => 'Test message',
                        'parameters' => [
                            'value'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider compileDataProvider
     * @param array $options
     * @param string|null $message
     * @param string $expected
     */
    public function testCompile(array $options, $message, $expected)
    {
        $this->condition->initialize($options);
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->compile('$factory');
        $this->assertEquals($expected, $actual);
    }

    public function compileDataProvider()
    {
        return [
            [
                'options'  => ['value'],
                'message'  => null,
                'expected' => '$factory->create(\'no_elements\', [\'value\'])'
            ],
            [
                'options'  => ['value'],
                'message'  => 'Test',
                'expected' => '$factory->create(\'no_elements\', [\'value\'])->setMessage(\'Test\')'
            ],
            [
                'options'  => [new PropertyPath('foo[bar].baz')],
                'message'  => null,
                'expected' => '$factory->create(\'no_elements\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath('
                    . '\'foo[bar].baz\', [\'foo\', \'bar\', \'baz\'], [false, true, false])'
                    . '])'
            ]
        ];
    }
}
