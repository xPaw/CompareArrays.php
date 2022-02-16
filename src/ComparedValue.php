<?php
declare(strict_types=1);

namespace xPaw\CompareArrays;

class ComparedValue
{
	const TYPE_ADDED = 'added';
	const TYPE_REMOVED = 'removed';
	const TYPE_MODIFIED = 'modified';

	public mixed $OldValue;
	public mixed $NewValue;
	public string $Type;

	/**
	 * @param self::TYPE_* $Type
	 */
	function __construct( string $Type, mixed $OldValue, mixed $NewValue )
	{
		$this->OldValue = $OldValue;
		$this->NewValue = $NewValue;
		$this->Type = $Type;
	}
}
