<?php

namespace App\Support\Translation;

use RuntimeException;

/**
 * The translation service is not configured, unreachable or refused the request.
 * The message is shown to the admin.
 */
final class TranslationFailed extends RuntimeException {}
