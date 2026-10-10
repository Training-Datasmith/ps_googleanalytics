<?php

use PHPUnit\Framework\TestCase;
use Tests\Support\GuardSubprocessDiagnostics;

class PsVersionGuardTest extends TestCase
{
    private const EXPECTED_TOTAL = 5;
    private const FILES = [
        'ps_googleanalytics.php',
        'upgrade/install-3.1.0.php',
        'upgrade/upgrade-4.1.0.php',
        'upgrade/upgrade-4.2.0.php',
        'upgrade/upgrade-5.0.0.php'
    ];

    public function testGuardVersionFileInventoryMatchesRepository()
    {
        $found = ['ps_googleanalytics.php'];
        foreach (glob(MODULE_ROOT . '/upgrade/*.php') as $absolute) {
            $base = basename($absolute);
            if ($base === 'index.php') {
                continue;
            }
            $found[] = 'upgrade/' . $base;
        }
        sort($found);
        $expected = self::FILES;
        sort($expected);
        $this->assertSame(self::EXPECTED_TOTAL, count($expected));
        $this->assertSame($expected, $found);
    }

    public function testGuardSubprocessUsesMaxErrorReporting()
    {
        $result = $this->runWrapper(MODULE_ROOT . '/index.php', 'index');
        $this->assertSame(0, $result['exit']);
        $this->assertSame(-1, $result['error_reporting']);
    }

    public function testGuardOffExitsBeforeSentinel()
    {
        foreach (self::FILES as $rel) {
            $tmp = sys_get_temp_dir() . '/ps-ga-guard-' . uniqid('', true);
            mkdir($tmp);
            $dest = $tmp . '/' . basename($rel);
            copy(MODULE_ROOT . '/' . $rel, $dest);
            $result = $this->runWrapper($dest, 'guard-off');
            $this->assertSame(0, $result['exit']);
            $this->assertFalse($result['sentinel'], $rel);
            $this->assertFalse($result['class_exists']);
            unlink($dest);
            rmdir($tmp);
        }
    }

    public function testGuardOnLoadsExpectedSymbols()
    {
        $map = [
            'upgrade/install-3.1.0.php' => 'upgrade_module_3_1_0',
            'upgrade/upgrade-4.1.0.php' => 'upgrade_module_4_1_0',
            'upgrade/upgrade-4.2.0.php' => 'upgrade_module_4_2_0',
            'upgrade/upgrade-5.0.0.php' => 'upgrade_module_5_0_0',
        ];
        foreach (self::FILES as $rel) {
            $tmp = sys_get_temp_dir() . '/ps-ga-guard-on-' . uniqid('', true);
            mkdir($tmp);
            $dest = $tmp . '/' . basename($rel);
            copy(MODULE_ROOT . '/' . $rel, $dest);
            $result = $this->runWrapper($dest, 'guard-on');
            $this->assertSame(0, $result['exit']);
            $this->assertTrue($result['sentinel'], $rel);
            if ($rel === 'ps_googleanalytics.php') {
                $this->assertTrue($result['class_exists']);
            } elseif (isset($map[$rel])) {
                $this->assertTrue($result['functions'][$map[$rel]]);
            }
            unlink($dest);
            rmdir($tmp);
        }
    }

    private function runWrapper($target, $mode)
    {
        $resultFile = sys_get_temp_dir() . '/guard-' . uniqid('', true) . '.json';
        $cmd = escapeshellarg(PHP_BINARY) . ' -d error_reporting=-1 '
            . escapeshellarg(TESTS_ROOT . '/bin/include-wrapper.php')
            . ' ' . escapeshellarg($target) . ' ' . escapeshellarg($mode);
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $env = ['GUARD_RESULT' => $resultFile];
        $proc = proc_open($cmd, $descriptors, $pipes, MODULE_ROOT, $env);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);
        $this->assertGuardChildHasNoDiagnostics($stdout, $stderr);
        $payload = [];
        if (is_file($resultFile)) {
            $payload = json_decode(file_get_contents($resultFile), true);
            unlink($resultFile);
        }

        return [
            'exit' => $exit,
            'sentinel' => !empty($payload['sentinel']),
            'class_exists' => !empty($payload['class_exists']),
            'functions' => isset($payload['functions']) ? $payload['functions'] : [],
            'error_reporting' => isset($payload['error_reporting']) ? $payload['error_reporting'] : null,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }

    private function assertGuardChildHasNoDiagnostics($stdout, $stderr)
    {
        $marker = GuardSubprocessDiagnostics::findMarker($stdout, $stderr);
        $this->assertNull(
            $marker,
            'Guard subprocess emitted PHP diagnostic output' . ($marker ? ': ' . $marker : '')
        );
    }
}
