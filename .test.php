<?php
declare(strict_types=1);

class CompareArraysTests extends PHPUnit\Framework\TestCase
{
	public function testEqualArrayFindsNoDifferences( )
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
		$this->assertEquals( $s, [] );
	}

	public function testFindsDifferencesWhenFalseTypeChanges( )
	{
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
			'k2' => new ComparedValue( ComparedValue::TYPE_MODIFIED, '0', '0' ),
			'k3' => new ComparedValue( ComparedValue::TYPE_MODIFIED, '', null ),
		] );
	}

	public function testSimpleChanges( )
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

	public function testUnbalancedArrays( )
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

	public function testVeryDeepArrays( )
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

	public function testFlatten( )
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

	public function testCastArrayKeys( )
	{
		$s = CompareArrays::Diff( [], [
			1 => 'a',
			'1' => 'b',
			1.5 => 'c',
			true => 'd',
			null => 'this is a null',
		] );
		$flattened = CompareArrays::Flatten( $s );
		$this->assertEquals( $flattened, [
			1 => new ComparedValue( ComparedValue::TYPE_ADDED, null, 'd' ),
			'' => new ComparedValue( ComparedValue::TYPE_ADDED, null, 'this is a null' ),
		] );
	}

	public function testFlattenEmptyKeys( )
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
			]
		] );
		$this->assertEquals( $flattened, [
			'///hello' => 'world',
		] );
	}

	public function testFlattenChangeSeparator( )
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
		$this->assertEquals( $flattened, [
			'a@_@b@_@c@_@hello' => 'world',
		] );
	}

	public function testFlattenStartWithPath( )
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
		$this->assertEquals( $flattened, [
			'prependedpath.a.b.c.hello' => 'world',
		] );
	}
}
