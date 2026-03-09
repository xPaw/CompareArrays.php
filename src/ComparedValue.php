<?php
declare(strict_types=1);

namespace xPaw\CompareArrays;

class ComparedValue
{
	public const string TYPE_ADDED = 'added';
	public const string TYPE_REMOVED = 'removed';
	public const string TYPE_MODIFIED = 'modified';

	public mixed $OldValue;
	public mixed $NewValue;
	public string $Type;

	/**
	 * @param self::TYPE_* $Type
	 */
	public function __construct( string $Type, mixed $OldValue, mixed $NewValue )
	{
		$this->OldValue = $OldValue;
		$this->NewValue = $NewValue;
		$this->Type = $Type;
	}
}
