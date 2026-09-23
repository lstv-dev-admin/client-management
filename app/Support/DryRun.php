<?php

namespace App\Support;

class DryRun
{
    public static function enabled(): bool
    {
        return (bool) session('dry_run', false);
    }

    public static function toggle(): bool
    {
        $enabled = ! self::enabled();
        session(['dry_run' => $enabled]);

        return $enabled;
    }
}
