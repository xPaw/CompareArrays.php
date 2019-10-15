Compares two arrays and produces a new array of changes between these two arrays.
New array will be same level deep as the input arrays, and the deepest value will be `ComparedValue`,
which is an object describing the difference (added, removed, modified).

Optionally, use `CompareArrays::Flatten()` function to turn diff array
into a one dimensional array which will flatten keys into a single path.

Usage:
```php
$Differences = CompareArrays::Diff( $OldArray, $NewArray );
print_r( $Differences );

$Flattened = CompareArrays::Flatten( $Differences );
print_r( $Flattened );
```
