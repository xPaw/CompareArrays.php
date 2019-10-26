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
}
