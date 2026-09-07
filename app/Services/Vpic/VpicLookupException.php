<?php

namespace App\Services\Vpic;

use RuntimeException;

/**
 * Thrown when the vPIC API itself could not be reached or returned an
 * unusable response — never for a VIN that merely decoded incompletely.
 */
final class VpicLookupException extends RuntimeException {}
