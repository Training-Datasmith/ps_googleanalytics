<?php

use PHPUnit\Framework\TestCase;

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

    public function testGuardInventory()
    {
        $this->assertSame(self::EXPECTED_TOTAL, count(self::FILES));
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
            unlink($dest); rmdir($tmp);
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
            unlink($dest); rmdir($tmp);
        }
    }

    private function runWrapper($target, $mode)
    {
        $resultFile = sys_get_temp_dir() . '/guard-' . uniqid('', true) . '.json';
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(TESTS_ROOT . '/bin/include-wrapper.php')
            . ' ' . escapeshellarg($target) . ' ' . escapeshellarg($mode);
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open($cmd, $descriptors, $pipes, MODULE_ROOT, ['GUARD_RESULT' => $resultFile]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
        $exit = proc_close($proc);
        $payload = json_decode(file_get_contents($resultFile), true);
        unlink($resultFile);

        return [
            'exit' => $exit,
            'sentinel' => !empty($payload['sentinel']),
            'class_exists' => !empty($payload['class_exists']),
            'functions' => isset($payload['functions']) ? $payload['functions'] : [],
            'stderr' => $stderr,
        ];
    }
}
