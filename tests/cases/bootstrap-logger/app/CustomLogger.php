<?php

declare(strict_types=1);

namespace app\bootstrapLogger;

use yii\log\Logger as YiiLogger;

/**
 * A standalone global logger installed by the application's bootstrap callback.
 * It overrides `init()` (as the module logger does) only to avoid the unrelated
 * shutdown-registration retention root; the point is that the app replaces the
 * global logger while it is being built.
 */
final class CustomLogger extends YiiLogger
{
    public function init(): void
    {
    }
}
