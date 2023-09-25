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
}
