<?php
declare(strict_types=1);

use xPaw\CompareArrays\CompareArrays;
use xPaw\CompareArrays\ComparedValue;

require __DIR__ . '/vendor/autoload.php';

class CompareArraysTests extends \PHPUnit\Framework\TestCase
{
	public function testEqualArrayFindsNoDifferences() : void
	{
		$s = CompareArrays::Diff( [
			'k1' => 'string',
			'k2' => 1,
			'k3' => true,
			'k4' => null,
			'k5' => '',
			'k6' => 1.23456,
		], [
			'k1' => 'string',
			'k2' => 1,
			'k3' => true,
			'k4' => null,
			'k5' => '',
			'k6' => 1.23456,
		] );
		$this->assertSame( $s, [] );
	}

	public function testFindsDifferencesWhenFalseTypeChanges() : void
	{
		/** @var ComparedValue[] $s */
		$s = CompareArrays::Diff( [
			'k1' => null,
			'k2' => '0',
			'k3' => '',
		], [
			'k1' => false,
			'k2' => 0,
			'k3' => null,
		] );
		$this->assertEquals( $s, [
			'k1' => new ComparedValue( ComparedValue::TYPE_MODIFIED, null, false ),
			'k2' => new ComparedValue( ComparedValue::TYPE_MODIFIED, '0', 0 ),
			'k3' => new ComparedValue( ComparedValue::TYPE_MODIFIED, '', null ),
		] );
		$this->assertSame( $s[ 'k1' ]->OldValue, null );
		$this->assertSame( $s[ 'k1' ]->NewValue, false );
		$this->assertSame( $s[ 'k2' ]->OldValue, '0' );
		$this->assertSame( $s[ 'k2' ]->NewValue, 0 );
		$this->assertSame( $s[ 'k3' ]->OldValue, '' );
		$this->assertSame( $s[ 'k3' ]->NewValue, null );
	}

	public function testSimpleChanges() : void
	{
		$s = CompareArrays::Diff( [
			'modified' => 'cool string',
			'removed' => 'old string',
		], [
			'modified' => 'very cool string',
			'added' => 'new string',
		] );
		$this->assertEquals( $s, [
			'modified' => new ComparedValue( ComparedValue::TYPE_MODIFIED, 'cool string', 'very cool string' ),
			'removed' => new ComparedValue( ComparedValue::TYPE_REMOVED, 'old string', null ),
			'added' => new ComparedValue( ComparedValue::TYPE_ADDED, null, 'new string' ),
		] );
	}

	public function testFloatChanges() : void
	{
		$s = CompareArrays::Diff( [
			'float' => 0.17,
			'float2' => 0.17,
		], [
			'float' => 0.17,
			'float2' => 0.16,
		] );
		$this->assertEquals( $s, [
			'float2' => new ComparedValue( ComparedValue::TYPE_MODIFIED, 0.17, 0.16 ),
		] );
	}

	public function testUnbalancedArrays() : void
	{
		$s = CompareArrays::Diff( [
			'onearray' =>
			[
				'k1' => 'testDeep',
			]
		], [
			'k1' => 'test',
			'twoarray' =>
			[
				'k1' => 'testDeep',
			]
		] );
		$this->assertEquals( $s, [
			'onearray' => [
				'k1' => new ComparedValue( ComparedValue::TYPE_REMOVED, 'testDeep', null ),
			],
			'k1' => new ComparedValue( ComparedValue::TYPE_ADDED, null, 'test' ),
			'twoarray' => [
				'k1' => new ComparedValue( ComparedValue::TYPE_ADDED, null, 'testDeep' ),
			],
		] );
	}

	public function testVeryDeepArrays() : void
	{
		$s = CompareArrays::Diff( [], [
			'a1' =>
			[
				'a2' =>
				[
					'a3' =>
					[
						'hello' => 'world'
					]
				]
			]
		] );
		$this->assertEquals( $s, [
			'a1' =>
			[
				'a2' =>
				[
					'a3' =>
					[
						'hello' => new ComparedValue( ComparedValue::TYPE_ADDED, null, 'world' ),
					]
				]
			]
		] );
	}

	public function testFlatten() : void
	{
		$s = CompareArrays::Diff( [], [
			'a1' =>
			[
				'a2' =>
				[
					'a3' =>
					[
						'hello' => 'world'
					]
				]
			]
		] );
		$flattened = CompareArrays::Flatten( $s );
		$this->assertEquals( $flattened, [
			'a1/a2/a3/hello' => new ComparedValue( ComparedValue::TYPE_ADDED, null, 'world' ),
		] );
	}

	public function testCastArrayKeys() : void
	{
		$s = CompareArrays::Diff( [], [
			1 => 'a', // @phpstan-ignore-line
			'1' => 'b',
			true => 'd',
			null => 'this is a null',
		] );
		$flattened = CompareArrays::Flatten( $s );
		$this->assertEquals( $flattened, [
			1 => new ComparedValue( ComparedValue::TYPE_ADDED, null, 'd' ),
			'' => new ComparedValue( ComparedValue::TYPE_ADDED, null, 'this is a null' ),
		] );
	}

	public function testFlattenEmptyKeys() : void
	{
		$flattened = CompareArrays::Flatten( [
			'' =>
			[
				null =>
				[
					'' =>
					[
						'hello' => 'world'
					]
				]
			],
			'root' =>
			[
				null =>
				[
					'' =>
					[
						'hello' => 'world'
					]
				]
			],
		] );
		$this->assertSame( $flattened, [
			'///hello' => 'world',
			'root///hello' => 'world',
		] );
	}

	public function testFlattenChangeSeparator() : void
	{
		$flattened = CompareArrays::Flatten( [
			'a' =>
			[
				'b' =>
				[
					'c' =>
					[
						'hello' => 'world'
					]
				]
			]
		], '@_@' );
		$this->assertSame( $flattened, [
			'a@_@b@_@c@_@hello' => 'world',
		] );
	}

	public function testFlattenStartWithPath() : void
	{
		$flattened = CompareArrays::Flatten( [
			'a' =>
			[
				'b' =>
				[
					'c' =>
					[
						'hello' => 'world'
					]
				]
			]
		], '.', 'prependedpath' );
		$this->assertSame( $flattened, [
			'prependedpath.a.b.c.hello' => 'world',
		] );
	}

	public function testEmptyArrays() : void
	{
		$s = CompareArrays::Diff( [], [] );
		$this->assertSame( $s, [] );

		$s = CompareArrays::Diff( ['a' => 1], [] );
		$this->assertEquals( $s, [
			'a' => new ComparedValue( ComparedValue::TYPE_REMOVED, 1, null ),
		] );

		$s = CompareArrays::Diff( [], ['a' => 1] );
		$this->assertEquals( $s, [
			'a' => new ComparedValue( ComparedValue::TYPE_ADDED, null, 1 ),
		] );
	}

	public function testSpecialCharactersInKeys() : void
	{
		$s = CompareArrays::Diff( [
			'special/key' => 'value1',
			'key with spaces' => 'value2',
			'key-with-dashes' => 'value3',
			'key_with_underscores' => 'value4',
			'ключ на кириллице' => 'value5',
			'😀' => 'value6',
		], [
			'special/key' => 'changed1',
			'key with spaces' => 'value2',
			'key-with-dashes' => 'changed3',
			'key_with_underscores' => 'value4',
			'ключ на кириллице' => 'value5',
			'😀' => 'changed6',
		] );

		$this->assertEquals( $s, [
			'special/key' => new ComparedValue( ComparedValue::TYPE_MODIFIED, 'value1', 'changed1' ),
			'key-with-dashes' => new ComparedValue( ComparedValue::TYPE_MODIFIED, 'value3', 'changed3' ),
			'😀' => new ComparedValue( ComparedValue::TYPE_MODIFIED, 'value6', 'changed6' ),
		] );

		$flattened = CompareArrays::Flatten( $s );
		$this->assertEquals( count( $flattened ), 3 );
		$this->assertArrayHasKey( 'special/key', $flattened );
		$this->assertArrayHasKey( 'key-with-dashes', $flattened );
		$this->assertArrayHasKey( '😀', $flattened );
	}

	public function testNumericStringVsIntegerKeys() : void
	{
		$s = CompareArrays::Diff( [
			'0' => 'string zero',
			'1' => 'string one',
			2 => 'int two',
		], [
			0 => 'int zero',
			1 => 'int one',
			'2' => 'string two',
		] );

		// In PHP, numeric strings are automatically converted to integers when used as array keys
		// so we expect only changes in values, not in keys
		$this->assertEquals( $s, [
			0 => new ComparedValue( ComparedValue::TYPE_MODIFIED, 'string zero', 'int zero' ),
			1 => new ComparedValue( ComparedValue::TYPE_MODIFIED, 'string one', 'int one' ),
			2 => new ComparedValue( ComparedValue::TYPE_MODIFIED, 'int two', 'string two' ),
		] );
	}

	public function testFloatEdgeCases() : void
	{
		$s = CompareArrays::Diff( [
			'inf' => INF,
			'ninf' => -INF,
			'nan' => NAN,
			'tiny' => 1.0e-100,
			'huge' => 1.0e+100,
			'epsilon1' => 0.1,
			'epsilon2' => 0.1 + PHP_FLOAT_EPSILON / 2,
		], [
			'inf' => INF,
			'ninf' => -INF,
			'nan' => NAN,
			'tiny' => 1.0e-100 + 1.0e-110, // Tiny difference
			'huge' => 1.0e+100 + 1.0e+90,  // Large difference
			'epsilon1' => 0.1,
			'epsilon2' => 0.1 + PHP_FLOAT_EPSILON * 2, // Just over epsilon
		] );

		// NAN !== NAN in PHP, but we expect the diff to handle this
		// INF === INF and -INF === -INF so those should match
		// Tiny difference should be within epsilon
		// Large difference should be detected
		// epsilon1 should not change
		// epsilon2 should change because it's over PHP_FLOAT_EPSILON
		$this->assertEquals( array_keys( $s ), ['nan', 'huge', 'epsilon2'] );
		$this->assertInstanceOf( ComparedValue::class, $s['epsilon2'] );
		$this->assertEquals( $s['epsilon2']->Type, ComparedValue::TYPE_MODIFIED );
	}

	public function testNestedArraysWithMixedKeys() : void
	{
		$s = CompareArrays::Diff( [
			'mixed' => [
				0 => 'zero',
				'1' => 'one',
				'string' => 'string value',
			]
		], [
			'mixed' => [
				0 => 'zero changed',
				'1' => 'one',
				'string' => 'string value changed',
				'new' => 'new value',
			]
		] );

		$this->assertEquals( $s, [
			'mixed' => [
				0 => new ComparedValue( ComparedValue::TYPE_MODIFIED, 'zero', 'zero changed' ),
				'string' => new ComparedValue( ComparedValue::TYPE_MODIFIED, 'string value', 'string value changed' ),
				'new' => new ComparedValue( ComparedValue::TYPE_ADDED, null, 'new value' ),
			]
		] );
	}

	public function testBooleanAndIntegerKeys() : void
	{
		// In PHP, true is cast to 1 and false is cast to 0 when used as array keys
		// Create separate tests for clarity

		// Test 1: Just boolean keys
		$s1 = CompareArrays::Diff( [
			true => 'true value',
			false => 'false value',
		], [
			true => 'true changed',
			false => 'false changed',
		] );

		$this->assertEquals( count( $s1 ), 2 );
		$this->assertInstanceOf( ComparedValue::class, $s1[0] );
		$this->assertInstanceOf( ComparedValue::class, $s1[1] );
		$this->assertEquals( $s1[1]->OldValue, 'true value' );
		$this->assertEquals( $s1[1]->NewValue, 'true changed' );
		$this->assertEquals( $s1[0]->OldValue, 'false value' );
		$this->assertEquals( $s1[0]->NewValue, 'false changed' );

		// Test 2: Just integer keys
		$s2 = CompareArrays::Diff( [
			1 => 'one value',
			0 => 'zero value',
		], [
			1 => 'one changed',
			0 => 'zero changed',
		] );

		$this->assertEquals( count( $s2 ), 2 );
		$this->assertInstanceOf( ComparedValue::class, $s2[0] );
		$this->assertInstanceOf( ComparedValue::class, $s2[1] );
		$this->assertEquals( $s2[1]->OldValue, 'one value' );
		$this->assertEquals( $s2[1]->NewValue, 'one changed' );
		$this->assertEquals( $s2[0]->OldValue, 'zero value' );
		$this->assertEquals( $s2[0]->NewValue, 'zero changed' );

		// Test 3: Mixed keys - the last defined value for each key wins
		$a1 = [
			1 => 'one value',
			0 => 'zero value',
		];

		$a2 = [
			true => 'true changed',
			false => 'false changed',
		];

		$s3 = CompareArrays::Diff( $a1, $a2 );
		$this->assertEquals( count( $s3 ), 2 );

		// Use individual assertions instead of full array comparison
		$this->assertArrayHasKey( 1, $s3 );
		$this->assertArrayHasKey( 0, $s3 );
		$this->assertInstanceOf( ComparedValue::class, $s3[1] );
		$this->assertInstanceOf( ComparedValue::class, $s3[0] );
	}

	public function testNonScalarValuesInArrays() : void
	{
		// Create a simple stdClass object
		$obj1 = new \stdClass();
		$obj1->prop = 'value';

		$obj2 = new \stdClass();
		$obj2->prop = 'different value';

		$s = CompareArrays::Diff( [
			'object' => $obj1,
		], [
			'object' => $obj2,
		] );

		// Objects with different property values should be detected as different
		$this->assertNotEmpty( $s );
		$this->assertInstanceOf( ComparedValue::class, $s['object' ] );
		$this->assertEquals( $s['object']->Type, ComparedValue::TYPE_MODIFIED );
		$this->assertSame( $s['object']->OldValue, $obj1 );
		$this->assertSame( $s['object']->NewValue, $obj2 );
	}

	public function testArrayToNonArray() : void
	{
		$s = CompareArrays::Diff( [
			'key' => ['nested' => 'value'],
		], [
			'key' => 'string value',
		] );

		// First, let's just verify the diff isn't empty
		$this->assertNotEmpty( $s );

		// Inspect the actual structure
		$this->assertArrayHasKey( 'key', $s );
		$this->assertIsArray( $s['key'] );
		$this->assertArrayHasKey( 'nested', $s['key'] );
		$this->assertInstanceOf( ComparedValue::class, $s['key']['nested'] );
		$this->assertEquals( ComparedValue::TYPE_REMOVED, $s['key']['nested']->Type );
		$this->assertEquals( 'value', $s['key']['nested']->OldValue );
		$this->assertNull( $s['key']['nested']->NewValue );

		// Test the reverse scenario
		$s2 = CompareArrays::Diff( [
			'key' => 'string value',
		], [
			'key' => ['nested' => 'value'],
		] );

		$this->assertNotEmpty( $s2 );
		$this->assertArrayHasKey( 'key', $s2 );
		$this->assertIsArray( $s2['key'] );
		$this->assertArrayHasKey( 'nested', $s2['key'] );
		$this->assertInstanceOf( ComparedValue::class, $s2['key']['nested'] );
		$this->assertEquals( ComparedValue::TYPE_ADDED, $s2['key']['nested']->Type );
		$this->assertNull( $s2['key']['nested']->OldValue );
		$this->assertEquals( 'value', $s2['key']['nested']->NewValue );
	}

	public function testFlattenWithComparedValues() : void
	{
		$diff = [
			'a' => new ComparedValue( ComparedValue::TYPE_MODIFIED, 'old', 'new' ),
			'b' => [
				'c' => new ComparedValue( ComparedValue::TYPE_ADDED, null, 'value' ),
			]
		];

		$flattened = CompareArrays::Flatten( $diff );
		$this->assertEquals( $flattened, [
			'a' => new ComparedValue( ComparedValue::TYPE_MODIFIED, 'old', 'new' ),
			'b/c' => new ComparedValue( ComparedValue::TYPE_ADDED, null, 'value' ),
		] );
	}

	public function testFlattenWithMixedTypesAndEmptyStrings() : void
	{
		$diff = [
			'' => 'empty key value',
			'0' => 'string zero key value',
			' ' => 'space key value',
		];

		$flattened = CompareArrays::Flatten( $diff );
		$this->assertCount( 3, $flattened );
		$this->assertArrayHasKey( '', $flattened );
		$this->assertArrayHasKey( '0', $flattened );
		$this->assertArrayHasKey( ' ', $flattened );
	}
}
