<?php
/**
 * Regression runner 21 test case (SC-01..12, INT-01..05, PAR-01..04).
 * Issue: W7-KEN-09, W7-KEN-11
 * Pemilik: Kenjiro B.H.G.
 *
 * Menjalankan seluruh suite yang tersedia dan menghasilkan ringkasan status
 * per test case. Test yang membutuhkan Ganache/MySQL akan gagal dengan status
 * FAIL yang eksplisit bila environment tidak siap.
 *
 * Jalankan:
 *   php tests/regression/run_all.php
 *   php tests/regression/run_all.php --skip-sc
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$evidenceDir = $root . '/docs/week7/evidence';
@mkdir($evidenceDir, 0777, true);

function regOptions(array $argv): array
{
    $options = [];
    foreach (array_slice($argv, 1) as $arg) {
        if (str_starts_with($arg, '--')) {
            $parts = explode('=', substr($arg, 2), 2);
            $options[$parts[0]] = $parts[1] ?? true;
        }
    }

    return $options;
}

/**
 * @param array<int,string> $command
 * @return array{exit_code:int,stdout:string,stderr:string}
 */
function regRun(array $command, string $cwd): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptors, $pipes, $cwd);
    if (!is_resource($process)) {
        return ['exit_code' => -1, 'stdout' => '', 'stderr' => 'Gagal membuat proses.'];
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    return [
        'exit_code' => $exitCode,
        'stdout' => $stdout === false ? '' : $stdout,
        'stderr' => $stderr === false ? '' : $stderr,
    ];
}

/**
 * Varian regRun yang menerima command line utuh (string). Dipakai untuk
 * `npm test`, karena proc_open mode array mengutip tiap argumen sehingga
 * `cmd.exe /c "npm test"` dianggap satu nama program oleh cmd.exe.
 *
 * @return array{exit_code:int,stdout:string,stderr:string}
 */
function regRunShell(string $command, string $cwd): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptors, $pipes, $cwd);
    if (!is_resource($process)) {
        return ['exit_code' => -1, 'stdout' => '', 'stderr' => 'Gagal membuat proses.'];
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    return [
        'exit_code' => $exitCode,
        'stdout' => $stdout === false ? '' : $stdout,
        'stderr' => $stderr === false ? '' : $stderr,
    ];
}

function regStripAnsi(string $text): string
{
    return preg_replace('/\x1b\[[0-9;]*m/', '', $text) ?? $text;
}

function regReadJson(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }
    $decoded = json_decode((string)file_get_contents($path), true);

    return is_array($decoded) ? $decoded : null;
}

function regWrite(string $path, string $content): void
{
    @mkdir(dirname($path), 0777, true);
    file_put_contents($path, $content);
}

$options = regOptions($argv);
$startedAt = microtime(true);
$tests = [];
$suites = [];

function regRecord(array &$tests, string $code, string $name, string $requirement, string $status, string $detail): void
{
    $tests[$code] = [
        'code' => $code,
        'name' => $name,
        'requirement' => $requirement,
        'status' => $status,
        'detail' => $detail,
    ];
}

echo "===============================================================\n";
echo "   H-SPORT — FULL REGRESSION 21 TEST (M7)\n";
echo "   Waktu: " . date('Y-m-d H:i:s') . "\n";
echo "===============================================================\n\n";

// ---------------------------------------------------------------------
// SC-01..SC-12
// ---------------------------------------------------------------------
if (!isset($options['skip-sc'])) {
    echo "[1/4] Smart contract SC-01..SC-12...\n";
    $run = regRunShell('cmd.exe /c npm test', $root . '/blockchain');
    $output = regStripAnsi($run['stdout'] . "\n" . $run['stderr']);
    regWrite($evidenceDir . '/sc_regression.log', $output);

    preg_match_all('/✔\s+(SC-\d{2}):\s*([^\r\n]+)/', $output, $matches, PREG_SET_ORDER);
    $found = [];
    foreach ($matches as $match) {
        $code = strtoupper($match[1]);
        $found[$code] = trim($match[2]);
    }
    for ($i = 1; $i <= 12; $i++) {
        $code = sprintf('SC-%02d', $i);
        regRecord(
            $tests,
            $code,
            $found[$code] ?? 'Tidak ditemukan di output',
            'REQ kontrak',
            isset($found[$code]) ? 'PASS' : 'FAIL',
            isset($found[$code]) ? $found[$code] : 'Suite tidak mencetak hasil untuk test ini.'
        );
    }
    $suites['SC'] = [
        'exit_code' => $run['exit_code'],
        'found' => count($found),
        'log' => 'docs/week7/evidence/sc_regression.log',
    ];
    echo '      SC ditemukan: ' . count($found) . "/12 | exit={$run['exit_code']}\n\n";
} else {
    foreach (range(1, 12) as $i) {
        regRecord($tests, sprintf('SC-%02d', $i), 'Dilewati', '-', 'FAIL', '--skip-sc dipakai.');
    }
    $suites['SC'] = ['exit_code' => null, 'found' => 0, 'log' => null];
}

// ---------------------------------------------------------------------
// INT-01..INT-05
// ---------------------------------------------------------------------
if (!isset($options['skip-int'])) {
    echo "[2/4] Integrasi INT-01..INT-05...\n";
    $run = regRun([PHP_BINARY, $root . '/tests/integration/run_all.php'], $root);
    $output = regStripAnsi($run['stdout'] . "\n" . $run['stderr']);
    regWrite($evidenceDir . '/int_regression.log', $output);

    preg_match_all('/\[[^\]]*PASS[^\]]*\]\s+(INT-\d{2}):\s*([^\r\n]+)/u', $output, $matches, PREG_SET_ORDER);
    $found = [];
    foreach ($matches as $match) {
        $found[strtoupper($match[1])] = trim($match[2]);
    }
    for ($i = 1; $i <= 5; $i++) {
        $code = sprintf('INT-%02d', $i);
        regRecord(
            $tests,
            $code,
            $found[$code] ?? 'Tidak ditemukan di output',
            'REQ integrasi',
            isset($found[$code]) ? 'PASS' : 'FAIL',
            isset($found[$code]) ? $found[$code] : 'Suite tidak mencetak PASS untuk test ini.'
        );
    }
    $suites['INT'] = [
        'exit_code' => $run['exit_code'],
        'found' => count($found),
        'log' => 'docs/week7/evidence/int_regression.log',
    ];
    echo '      INT ditemukan: ' . count($found) . "/5 | exit={$run['exit_code']}\n\n";
} else {
    foreach (range(1, 5) as $i) {
        regRecord($tests, sprintf('INT-%02d', $i), 'Dilewati', '-', 'FAIL', '--skip-int dipakai.');
    }
    $suites['INT'] = ['exit_code' => null, 'found' => 0, 'log' => null];
}

// ---------------------------------------------------------------------
// PAR-01 & PAR-02
// ---------------------------------------------------------------------
echo "[3/4] Parallel PAR-01 & PAR-02...\n";
$run = regRun([PHP_BINARY, $root . '/tests/parallel/run_par.php'], $root);
$output = regStripAnsi($run['stdout'] . "\n" . $run['stderr']);
regWrite($evidenceDir . '/par01_par02_regression.log', $output);
$parJson = regReadJson($root . '/docs/week6/evidence/par01_par02_test.json');
$parChecks = is_array($parJson['checks'] ?? null) ? $parJson['checks'] : [];

$par01Checks = array_values(array_filter($parChecks, static fn(array $check): bool => str_starts_with((string)$check['code'], 'PAR-01')));
$par02Checks = array_values(array_filter($parChecks, static fn(array $check): bool => str_starts_with((string)$check['code'], 'PAR-02')));
$par01Pass = !empty($par01Checks) && count(array_filter($par01Checks, static fn(array $c): bool => $c['status'] === 'PASS')) === count($par01Checks);
$par02Pass = !empty($par02Checks) && count(array_filter($par02Checks, static fn(array $c): bool => $c['status'] === 'PASS')) === count($par02Checks);

regRecord($tests, 'PAR-01', 'Sequential baseline: waktu & agregasi', 'REQ-13', $par01Pass ? 'PASS' : 'FAIL',
    'checks=' . count($par01Checks) . ' checksum=' . substr((string)($parJson['par01']['checksum'] ?? '-'), 0, 16));
regRecord($tests, 'PAR-02', 'Parallel partition/reduce = sequential', 'REQ-13', $par02Pass ? 'PASS' : 'FAIL',
    'checks=' . count($par02Checks) . ' runs=' . count($parJson['par02']['runs'] ?? []));
$suites['PAR-01/02'] = [
    'exit_code' => $run['exit_code'],
    'checks' => count($parChecks),
    'log' => 'docs/week7/evidence/par01_par02_regression.log',
];
echo '      checks=' . count($parChecks) . " | exit={$run['exit_code']}\n\n";

// ---------------------------------------------------------------------
// PAR-03 & PAR-04
// ---------------------------------------------------------------------
echo "[4/4] Benchmark PAR-03 & PAR-04...\n";
$run = regRun([PHP_BINARY, $root . '/tests/parallel/run_par03_par04.php'], $root);
$output = regStripAnsi($run['stdout'] . "\n" . $run['stderr']);
regWrite($evidenceDir . '/par03_par04_regression.log', $output);
$par34Json = regReadJson($evidenceDir . '/par03_par04_test.json');
$par34Checks = is_array($par34Json['checks'] ?? null) ? $par34Json['checks'] : [];

$par03Checks = array_values(array_filter($par34Checks, static fn(array $check): bool => str_starts_with((string)$check['code'], 'PAR-03')));
$par04Checks = array_values(array_filter($par34Checks, static fn(array $check): bool => str_starts_with((string)$check['code'], 'PAR-04')));
$par03Pass = !empty($par03Checks) && count(array_filter($par03Checks, static fn(array $c): bool => $c['status'] === 'PASS')) === count($par03Checks);
$par04Pass = !empty($par04Checks) && count(array_filter($par04Checks, static fn(array $c): bool => $c['status'] === 'PASS')) === count($par04Checks);

regRecord($tests, 'PAR-03', 'Throughput, speedup, efficiency', 'REQ-14', $par03Pass ? 'PASS' : 'FAIL',
    'checks=' . count($par03Checks) . ' rows=' . count(regReadJson($evidenceDir . '/benchmark_matrix.json')['rows'] ?? []));
regRecord($tests, 'PAR-04', 'Ketahanan konkurensi & integritas agregasi', 'REQ-14, NFR-01', $par04Pass ? 'PASS' : 'FAIL',
    'checks=' . count($par04Checks));
$suites['PAR-03/04'] = [
    'exit_code' => $run['exit_code'],
    'checks' => count($par34Checks),
    'log' => 'docs/week7/evidence/par03_par04_regression.log',
];
echo '      checks=' . count($par34Checks) . " | exit={$run['exit_code']}\n\n";

// ---------------------------------------------------------------------
// Ringkasan
// ---------------------------------------------------------------------
$orderedCodes = [];
for ($i = 1; $i <= 12; $i++) {
    $orderedCodes[] = sprintf('SC-%02d', $i);
}
for ($i = 1; $i <= 5; $i++) {
    $orderedCodes[] = sprintf('INT-%02d', $i);
}
for ($i = 1; $i <= 4; $i++) {
    $orderedCodes[] = sprintf('PAR-%02d', $i);
}

$orderedTests = [];
foreach ($orderedCodes as $code) {
    $orderedTests[] = $tests[$code] ?? [
        'code' => $code,
        'name' => 'Tidak dijalankan',
        'requirement' => '-',
        'status' => 'FAIL',
        'detail' => 'Tidak ada hasil.',
    ];
}

$passed = count(array_filter($orderedTests, static fn(array $test): bool => $test['status'] === 'PASS'));
$failed = count($orderedTests) - $passed;
$elapsed = round(microtime(true) - $startedAt, 2);

$summary = [
    'executed_at' => date('c'),
    'environment' => [
        'php' => PHP_VERSION,
        'os' => PHP_OS_FAMILY,
        'contract_address' => trim((string)@file_get_contents($root . '/blockchain/config/contract_address.txt')),
        'ganache_rpc' => 'http://127.0.0.1:7545',
        'mysql' => 'h_sport_db@127.0.0.1:3306',
    ],
    'suites' => $suites,
    'total' => count($orderedTests),
    'passed' => $passed,
    'failed' => $failed,
    'all_pass' => $failed === 0,
    'elapsed_seconds' => $elapsed,
    'tests' => $orderedTests,
];

regWrite($evidenceDir . '/regression_summary.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

$md = [];
$md[] = '# Regression 21 Test — Ringkasan';
$md[] = '';
$md[] = '**Dibuat:** ' . date('Y-m-d H:i:s');
$md[] = '**Hasil:** ' . $passed . '/' . count($orderedTests) . ' PASS';
$md[] = '';
$md[] = '| # | Test | Requirement | Status | Detail |';
$md[] = '|---:|---|---|---|---|';
$index = 0;
foreach ($orderedTests as $test) {
    $index++;
    $md[] = '| ' . $index . ' | `' . $test['code'] . '` ' . $test['name']
        . ' | ' . $test['requirement']
        . ' | ' . $test['status']
        . ' | ' . str_replace('|', '\\|', $test['detail']) . ' |';
}
regWrite($evidenceDir . '/regression_summary.md', implode("\n", $md) . "\n");

echo "===============================================================\n";
echo "   HASIL AKHIR: {$passed} / " . count($orderedTests) . " TEST PASS\n";
echo "   Status: " . ($failed === 0 ? 'ALL PASS (100%)' : 'PARTIAL / FAIL') . "\n";
echo '   Durasi: ' . number_format($elapsed, 2, ',', '.') . " detik\n";
echo "===============================================================\n";
if ($failed > 0) {
    foreach ($orderedTests as $test) {
        if ($test['status'] !== 'PASS') {
            echo " - {$test['code']}: {$test['detail']}\n";
        }
    }
}

exit($failed === 0 ? 0 : 1);
