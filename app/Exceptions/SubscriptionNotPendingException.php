<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The payment an admin tried to review is no longer pending (another admin
 * already activated/rejected it, or it never existed).
 *
 * This is a narrow, expected business-state failure. It is deliberately its
 * own class so controllers never catch a broad RuntimeException and
 * accidentally disguise a database QueryException as a normal status message.
 */
class SubscriptionNotPendingException extends RuntimeException {}
