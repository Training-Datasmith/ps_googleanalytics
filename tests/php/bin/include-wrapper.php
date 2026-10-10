<?php
/**
 * Subprocess guard/index probe. Arguments: <target-file> <mode>
 * mode: index | guard-off | guard-on
 */
error_reporting(-1);
ini_set('display_errors', 'stderr');

if ($argc < 3) {
    fwrite(STDERR, "usage: include-wrapper.php <file> <mode>\n");
    exit(2);
}

$targetFile = $argv[1];
$mode = $argv[2];
$resultPath = getenv('GUARD_RESULT');
if (!$resultPath) {
    fwrite(STDERR, "GUARD_RESULT env required\n");
    exit(2);
}

if (getenv('GUARD_INJECT_NOTICE')) {
    set_error_handler(function ($severity, $message) {
        if ($severity === E_NOTICE || $severity === E_USER_NOTICE) {
            fwrite(STDERR, $message);
            exit(2);
        }

        return false;
    });
    $guardInjectProbe = $undefinedNoticeVar;
}

$result = [
    'sentinel' => false,
    'exit_via_include' => false,
    'class_exists' => false,
    'functions' => [],
    'error_reporting' => error_reporting(),
];

$upgradeFunctions = [
    'upgrade_module_3_1_0',
    'upgrade_module_4_1_0',
    'upgrade_module_4_2_0',
    'upgrade_module_5_0_0',
];

register_shutdown_function(function () use ($resultPath, &$result, $upgradeFunctions) {
    $result['class_exists'] = class_exists('Ps_Googleanalytics', false);
    foreach ($upgradeFunctions as $fn) {
        $result['functions'][$fn] = function_exists($fn);
    }
    file_put_contents($resultPath, json_encode($result));
});

if ($mode === 'guard-on') {
    if (!defined('_PS_VERSION_')) {
        define('_PS_VERSION_', '8.1.0');
    }
    if (basename($targetFile) === 'ps_googleanalytics.php' && !class_exists('Module', false)) {
        if (!defined('_PS_MODULE_DIR_')) {
            define('_PS_MODULE_DIR_', dirname(dirname(dirname($targetFile))) . '/');
        }
        class Module
        {
            public function __construct()
            {
            }

            public function trans($id, $params, $domain)
            {
                return $id;
            }
        }
    }
}

ob_start();
include $targetFile;
ob_end_clean();
$result['sentinel'] = true;
$result['exit_via_include'] = true;
exit(0);
