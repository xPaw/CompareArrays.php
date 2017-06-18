<?php
/**
 * Diffing multi dimensional arrays the easy way.
 *
 * GitHub: {@link https://github.com/xPaw/CompareArrays.php}
 * Website: {@link https://xpaw.me}
 *
 * @author Pavel Djundik
 * @license MIT
 */
class CompareArrays2
{
	/**
	 * Flattens multi-dimensional array into one dimensional array,
	 * and turns keys into paths separated by $Separator (by default '/')
	 *
	 * @param array $Input
	 * @param string $Separator
	 */
	public static function Flatten( $Input, $Separator = '/', $Path = null )
	{
		$Data = [];
		
		if( !is_null( $Path ) )
		{
			$Path .= $Separator;
		}
		
		foreach( $Input as $Key => $Value )
		{
			if( is_array( $Value ) )
			{
				$Data = array_merge( $Data, self::Flatten( $Value, $Separator, $Path . $Key ) );
			}
			else
			{
				$Data[ $Path . $Key ] = $Value;
			}
		}
		
		return $Data;
	}
	
	/**
	 * Compares two arrays and produces a new array of changes between these
	 * two arrays. New array will be same level deep as the input arrays,
	 * and the deepest value will be `ComparedValue`, which is an object
	 * describing the difference (added, removed, modified).
	 * 
	 * Optionally, use CompareArrays::Flatten() function to turn diff array
	 * into a one dimensional array which will flatten keys into a single path.
	 *
	 * @param array $Old
	 * @param array $New
	 */
	public static function Diff( $Old, $New )
	{
		$Diff = [];
		
		if( $Old == $New )
		{
			return $Diff;
		}
		
		foreach( $Old as $Key => $Value )
		{
			if( !isset( $New[ $Key ] ) )
			{
				$Diff[ $Key ] = self::Singular( ComparedValue::TYPE_REMOVED, $Key, $Value );
				
				continue;
			}
			
			$ValueNew = $New[ $Key ];
			
			if( is_array( $ValueNew ) )
			{
				$Temp = self::Diff( $Value, $ValueNew );
				
				if( !empty( $Temp ) )
				{
					$Diff[ $Key ] = $Temp;
				}
				
				continue;
			}
			
			if( $Value != $ValueNew )
			{
				$Diff[ $Key ] = new ComparedValue( ComparedValue::TYPE_MODIFIED, $Value, $ValueNew );
			}
		}
		
		foreach( $New as $Key => $Value )
		{
			if( !isset( $Old[ $Key ] ) )
			{
				$Diff[ $Key ] = self::Singular( ComparedValue::TYPE_ADDED, $Key, $Value );
			}
		}
		
		return $Diff;
	}
	
	private static function Singular( $Type, $Key, $Value )
	{
		if( is_array( $Value ) )
		{
			$Diff = [];
			
			foreach( $Value as $Key2 => $Value2 )
			{
				$Diff[ $Key2 ] = self::Singular( $Type, $Key2, $Value2 );
			}
			
			return $Diff;
		}
		
		if( $Type === ComparedValue::TYPE_REMOVED )
		{
			return new ComparedValue( $Type, $Value, null );
		}
		
		return new ComparedValue( $Type, null, $Value );
	}
}

class ComparedValue
{
	const TYPE_ADDED = 'added';
	const TYPE_REMOVED = 'removed';
	const TYPE_MODIFIED = 'modified';
	
	public $OldValue;
	public $NewValue;
	public $Type;
	
	function __construct( $Type, $OldValue, $NewValue )
	{
		$this->OldValue = $OldValue;
		$this->NewValue = $NewValue;
		$this->Type = $Type;
	}
}
