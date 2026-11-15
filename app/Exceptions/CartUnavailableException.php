<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A cart row cannot be fulfilled (product deleted, deactivated, out of stock,
 * or the requested quantity exceeds stock) at the moment the order is created.
 *
 * This is an expected business-state failure: the customer is sent back to the
 * cart with a clear message. The checkout controller catches only this class,
 * so genuine database failures are still logged by Laravel and surface as
 * real errors instead of being disguised as "product unavailable".
 */
class CartUnavailableException extends RuntimeException {}
