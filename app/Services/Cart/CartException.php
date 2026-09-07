<?php

namespace App\Services\Cart;

use RuntimeException;

/**
 * Thrown for a rejected cart mutation (invalid quantity, unavailable
 * product, quantity beyond current stock). The message is safe to show
 * directly to the user.
 */
class CartException extends RuntimeException {}
