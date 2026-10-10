<?php

use PHPUnit\Framework\TestCase;
use Tests\Support\GuardSubprocessDiagnostics;

class IndexPhpGuardTest extends TestCase
{
    private const EXPECTED_TOTAL = 22;
    private const FILES = [
        'index.php',
        'classes/index.php',
        'classes/Database/index.php',
        'classes/Form/index.php',
        'classes/Handler/index.php',
        'classes/Hook/index.php',
        'classes/Repository/index.php',
        'classes/Wrapper/index.php',
        'controllers/index.php',
        'controllers/admin/index.php',
        'controllers/front/index.php',
        'translations/index.php',
        'upgrade/index.php',
        'views/index.php',
        'views/css/index.php',
        'views/img/index.php',
        'views/templates/index.php',
        'views/templates/admin/index.php',
        'views/templates/hook/index.php',
        'tests/index.php',
        'tests/php/index.php',
        'tests/php/phpstan/index.php'
    ];

    public function testIndexPhpInventoryMatchesRepository()
    {
        $found = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(MODULE_ROOT, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === 'index.php') {
                $path = str_replace('\\', '/', substr($file->getPathname(), strlen(MODULE_ROOT) + 1));
                if (strpos($path, '.git/') !== false) {
                    continue;
                }
                $found[] = $path;
            }
        }
        sort($found);
        $expected = self::FILES;
        sort($expected);
        $this->assertSame(self::EXPECTED_TOTAL, count($expected));
        $this->assertSame(self::EXPECTED_TOTAL, count($found));
        $this->assertSame($expected, $found);
    }

    public function testShuffledIndexCopiesExitWithoutRunningModuleCode()
    {
        $paths = self::FILES;
        shuffle($paths);
        $tmp = sys_get_temp_dir() . '/ps-ga-index-' . uniqid('', true);
        mkdir($tmp);
        foreach ($paths as $rel) {
            $destDir = $tmp . '/' . dirname($rel);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0777, true);
            }
            copy(MODULE_ROOT . '/' . $rel, $tmp . '/' . $rel);
        }
        foreach ($paths as $rel) {
            $result = $this->runWrapper($tmp . '/' . $rel, 'index');
            $this->assertSame(0, $result['exit']);
            $this->assertFalse($result['sentinel'], $rel);
        }
        $this->removeTree($tmp);
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
        $payload = json_decode(file_get_contents($resultFile), true);
        unlink($resultFile);

        return [
            'exit' => $exit,
            'sentinel' => !empty($payload['sentinel']),
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

    private function removeTree($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeTree($path) : unlink($path);
        }
        rmdir($dir);
    }
}
