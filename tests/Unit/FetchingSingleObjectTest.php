<?php

namespace DoctrineMapper\Tests\Unit;

use DoctrineMapper\Lib\Exception\NotTypedClassProperty;
use DoctrineMapper\Lib\Exception\RequiredParamNotProvided;
use DoctrineMapper\Lib\Service\DbalObjectConverter;
use DoctrineMapper\Tests\Unit\Fixture\ClassWithConstructor;
use DoctrineMapper\Tests\Unit\Fixture\ClassWithNotTypedProperty;
use DoctrineMapper\Tests\Unit\Fixture\Person;
use Doctrine\DBAL\Result;
use Mockery;
use PHPUnit\Framework\TestCase;

class FetchingSingleObjectTest extends TestCase
{
    private DbalObjectConverter $serviceUnderTest;

    protected function setUp(): void
    {
        $this->serviceUnderTest = new DbalObjectConverter();
    }

    public function testConvertToSingleObjectWithPublicProperties(): void
    {
        /** @Given */
        $result = Mockery::mock(Result::class);
        $result
            ->expects('fetchAssociative')
            ->withAnyArgs()
            ->andReturn(
                [
                    'id' => '20',
                    'name' => 'John',
                    'age' => '25.5',
                    'married' => '1',
                ],
                [
                    'id' => '450',
                    'name' => 'Alice',
                    'age' => '80',
                    'married' => '0'
                ]
            );

        $firstExpected = new Person();
        $firstExpected->id = 20;
        $firstExpected->name = 'John';
        $firstExpected->age = 25.5;
        $firstExpected->married = true;

        $secondExpected = new Person();
        $secondExpected->id = 450;
        $secondExpected->name = 'Alice';
        $secondExpected->age = 80;
        $secondExpected->married = false;

        /** @When */
        $firstObject = $this->serviceUnderTest->fetchObject($result, Person::class);
        $secondObject = $this->serviceUnderTest->fetchObject($result, Person::class);

        /** @Then */
        $this->assertEquals($firstExpected, $firstObject);
        $this->assertEquals($secondExpected, $secondObject);
    }

    public function testConvertToSingleObjectWithConstructor(): void
    {
        /** @Given */
        $result = Mockery::mock(Result::class);
        $result
            ->expects('fetchAssociative')
            ->withAnyArgs()
            ->andReturn(
                [
                    'name' => 'John',
                    'age' => '25.5',
                    'id' => '20',
                ]
            );

        $firstExpected = new ClassWithConstructor(20, 'John', 25.5);

        /** @When */
        $actual = $this->serviceUnderTest->fetchObject($result, ClassWithConstructor::class);

        /** @Then */
        $this->assertEquals($firstExpected, $actual);
    }

    public function testReturnNullIfNoResults(): void
    {
        /** @Given */
        $result = Mockery::mock(Result::class);
        $result
            ->expects('fetchAssociative')
            ->withAnyArgs()
            ->andReturnFalse();

        /** @When */
        $actual = $this->serviceUnderTest->fetchObject($result, Person::class);

        /** @Then */
        $this->assertNull($actual);
    }

    public function testThrowErrorOnNotProvidedNotNullableProperty(): void
    {
        /** @Given */
        $result = Mockery::mock(Result::class);
        $result
            ->expects('fetchAssociative')
            ->withAnyArgs()
            ->andReturn(
                [
                    'id' => '20',
                    'name' => 'John',
                    'age' => '25.5',
                ]
            );

        /** @Then */
        $this->expectException(RequiredParamNotProvided::class);
        $this->expectExceptionMessage('Parameter `married` which is required, is not provided');

        /** @When */
        $this->serviceUnderTest->fetchObject($result, Person::class);
    }

    public function testThrowErrorOnNotTypedProperty(): void
    {
        /** @Given */
        $result = Mockery::mock(Result::class);
        $result
            ->expects('fetchAssociative')
            ->withAnyArgs()
            ->andReturn(
                [
                    'id' => '20',
                    'name' => 'John',
                    'age' => '25.5',
                ]
            );

        /** @Then */
        $this->expectException(NotTypedClassProperty::class);
        $this->expectExceptionMessage('Object to convert must have all properties typed and property `id` is not typed');

        /** @When */
        $this->serviceUnderTest->fetchObject($result, ClassWithNotTypedProperty::class);
    }

    public function testFillNullableFieldsWithNull(): void
    {
        /** @Given */
        $result = Mockery::mock(Result::class);
        $result
            ->expects('fetchAssociative')
            ->withAnyArgs()
            ->andReturn(
                [
                    'id' => '20',
                    'name' => 'John',
                    'age' => '25.5',
                ]
            );

        /** @Then */
        $this->expectException(NotTypedClassProperty::class);
        $this->expectExceptionMessage('Object to convert must have all properties typed and property `id` is not typed');

        /** @When */
        $this->serviceUnderTest->fetchObject($result, ClassWithNotTypedProperty::class);
    }
}