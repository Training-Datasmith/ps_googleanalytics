<?php

namespace Tests\Support;

class GuardSubprocessDiagnostics
{
    private const MARKERS = [
        'Notice:',
        'Warning:',
        'Deprecated:',
        'Fatal error:',
    ];

    public static function findMarker($stdout, $stderr)
    {
        $combined = $stdout . $stderr;
        foreach (self::MARKERS as $marker) {
            if (strpos($combined, $marker) !== false) {
                return $marker;
            }
        }

        return null;
    }
}
