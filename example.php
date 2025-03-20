<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

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

// Using a dot as separator
$flattened = CompareArrays::Flatten($differences, '.');
print_r($flattened);