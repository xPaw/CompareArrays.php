# CompareArrays

[![Latest Stable Version](https://img.shields.io/packagist/v/xpaw/compare-arrays.svg)](https://packagist.org/packages/xpaw/compare-arrays)
[![License](https://img.shields.io/github/license/xPaw/CompareArrays.php.svg)](https://github.com/xPaw/CompareArrays.php/blob/master/LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/xpaw/compare-arrays.svg)](https://packagist.org/packages/xpaw/compare-arrays)

A PHP library for comparing multi-dimensional arrays and detecting differences.

## Features

- Deep comparison of multi-dimensional arrays
- Detects added, removed, and modified values
- Maintains array structure in the result
- Special handling for float comparison with epsilon
- Flatten results into a single-dimensional array with path keys

## Installation

Install via Composer:

```bash
composer require xpaw/compare-arrays
```

## Usage

### Basic Comparison

```php
use xPaw\CompareArrays\CompareArrays;

$oldArray = [
	'user' => [
		'name' => 'John',
		'age' => 30,
		'settings' => [
			'darkMode' => true,
			'notifications' => true
		]
	]
];

$newArray = [
	'user' => [
		'name' => 'John Doe',
		'age' => 30,
		'settings' => [
			'darkMode' => true,
			'notifications' => false
		],
		'lastLogin' => '2025-03-20'
	]
];

$differences = CompareArrays::Diff($oldArray, $newArray);
print_r($differences);
```

Result:

```
Array(
    [user] => Array(
		[name] => xPaw\CompareArrays\ComparedValue Object(
			[OldValue] => John
			[NewValue] => John Doe
			[Type] => modified
		)

		[settings] => Array(
			[notifications] => xPaw\CompareArrays\ComparedValue Object(
				[OldValue] => 1
				[NewValue] =>
				[Type] => modified
			)
		)

		[lastLogin] => xPaw\CompareArrays\ComparedValue Object(
			[OldValue] =>
			[NewValue] => 2025-03-20
			[Type] => added
		)
	)
)
```

### Flattening Results

To simplify handling of nested differences, you can flatten the result:

```php
$flattened = CompareArrays::Flatten($differences);
print_r($flattened);
```

Result:

```
Array(
    [user/name] => xPaw\CompareArrays\ComparedValue Object(
		[OldValue] => John
		[NewValue] => John Doe
		[Type] => modified
	)

    [user/settings/notifications] => xPaw\CompareArrays\ComparedValue Object(
		[OldValue] => 1
		[NewValue] =>
		[Type] => modified
	)

    [user/lastLogin] => xPaw\CompareArrays\ComparedValue Object(
		[OldValue] =>
		[NewValue] => 2025-03-20
		[Type] => added
	)
)
```

### Custom Separator and Path Prefix

You can customize the separator and add a path prefix when flattening:

```php
// Using a dot as separator
$flattened = CompareArrays::Flatten($differences, '.');
```

Result:

```
Array
(
    [user.name] => xPaw\CompareArrays\ComparedValue Object(...)
    [user.settings.notifications] => xPaw\CompareArrays\ComparedValue Object(...)
    [user.lastLogin] => xPaw\CompareArrays\ComparedValue Object(...)
)
```

## API Reference

### CompareArrays::Diff(array $Old, array $New): array

Compares two arrays and produces a new array of changes between these arrays.

- The result maintains the same structure as the input arrays
- The deepest values are `ComparedValue` objects
- Float values are compared with `PHP_FLOAT_EPSILON` for precision
- Special handling for arrays vs non-arrays in corresponding positions

### CompareArrays::Flatten(array $Input, string $Separator = '/', ?string $Path = null): array

Flattens a multi-dimensional array into a one-dimensional array.

- Keys are transformed into paths separated by the specified separator
- Optionally prepend a path prefix to all keys

### ComparedValue

Each detected difference is represented by a `ComparedValue` object with:

- `$Type`: One of `added`, `removed`, or `modified`
- `$OldValue`: The value from the old array (null for added items)
- `$NewValue`: The value from the new array (null for removed items)

## Special Cases

### Float Comparison

Floating-point values are compared with `PHP_FLOAT_EPSILON` to handle precision issues:

```php
$differences = CompareArrays::Diff(
	['value' => 0.1],
	['value' => 0.1 + 0.00000001]
);
// Result: empty array (no differences)

$differences = CompareArrays::Diff(
	['value' => 0.1],
	['value' => 0.1 + 0.0001]
);
// Result: detects difference
```

## License

This library is licensed under the [MIT License](LICENSE).
