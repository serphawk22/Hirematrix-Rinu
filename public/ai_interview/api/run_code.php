<?php
error_reporting(E_ALL); ini_set('display_errors', 0);
ob_start(); session_start(); ob_end_clean();
header('Content-Type: application/json');

$body  = json_decode(file_get_contents('php://input'), true);
$lang  = trim($body['language'] ?? '');
$code  = $body['code'] ?? '';
$stdin = $body['stdin'] ?? '';

if (!$lang || !$code) {
    echo json_encode(['success'=>false,'error'=>'Missing language or code']); exit;
}

// ── Language → file extension + run command ──────────────────────────────
$tmpDir = sys_get_temp_dir();
$id     = 'nexus_' . uniqid();

function findCommand(array $candidates) {
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    foreach ($candidates as $cmd) {
        $escaped = $isWindows ? "where $cmd" : "command -v $cmd";
        $path = trim(shell_exec($escaped));
        if (!$path) continue;
        $first = trim(explode("\n", $path)[0]);
        if ($first && file_exists($first)) {
            return $first;
        }
    }
    return '';
}

function runLocal($cmd, $stdin, $timeout = 10) {
    $desc = [0=>['pipe','r'], 1=>['pipe','w'], 2=>['pipe','w']];
    $proc = proc_open($cmd, $desc, $pipes);
    if (!is_resource($proc)) return ['stdout'=>'', 'stderr'=>'Failed to start process', 'code'=>1];

    fwrite($pipes[0], $stdin);
    fclose($pipes[0]);

    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $stdout = $stderr = '';
    $start  = time();
    $status = [];
    while (true) {
        $r = [$pipes[1], $pipes[2]]; $w = $e = [];
        stream_select($r, $w, $e, 0, 100000);
        $stdout .= fread($pipes[1], 8192);
        $stderr .= fread($pipes[2], 8192);
        $status  = proc_get_status($proc);
        if (!$status['running']) break;
        if (time() - $start >= $timeout) {
            proc_terminate($proc, 9);
            $stderr .= "\nTime Limit Exceeded ({$timeout}s)";
            break;
        }
    }
    $stdout .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    proc_close($proc);
    return ['stdout'=>$stdout, 'stderr'=>$stderr, 'code'=>$status['exitcode'] ?? 0];
}

// ── Execute based on language ──────────────────────────────────────────────
$result = null;
$tmpFiles = [];

try {
    if ($lang === 'python') {
        $f = "$tmpDir/{$id}.py";
        file_put_contents($f, $code); $tmpFiles[] = $f;
        $python = findCommand(['python3','python']);
        if (!$python) { echo json_encode(['success'=>false,'error'=>'Python interpreter not found']); goto cleanup; }
        $result = runLocal(escapeshellarg($python) . ' ' . escapeshellarg($f), $stdin);

    } elseif ($lang === 'javascript') {
        $f = "$tmpDir/{$id}.js";
        file_put_contents($f, $code); $tmpFiles[] = $f;
        $node = findCommand(['node','nodejs']);
        if (!$node) { echo json_encode(['success'=>false,'error'=>'Node.js runtime not found']); goto cleanup; }
        $result = runLocal(escapeshellarg($node) . ' ' . escapeshellarg($f), $stdin);

    } elseif ($lang === 'cpp') {
        $src = "$tmpDir/{$id}.cpp"; $bin = "$tmpDir/{$id}";
        file_put_contents($src, $code); $tmpFiles[] = $src; $tmpFiles[] = $bin;
        $gpp = findCommand(['g++']);
        if (!$gpp) { echo json_encode(['success'=>false,'error'=>'g++ compiler not found']); goto cleanup; }
        $compile = runLocal(escapeshellarg($gpp) . " -o " . escapeshellarg($bin) . " " . escapeshellarg($src), '');
        if ($compile['code'] !== 0 || !empty($compile['stderr'])) {
            echo json_encode(['success'=>true,'stdout'=>'','stderr'=>$compile['stderr'],'code'=>$compile['code']]); goto cleanup;
        }
        $result = runLocal(escapeshellarg($bin), $stdin);

    } elseif ($lang === 'c') {
        $src = "$tmpDir/{$id}.c"; $bin = "$tmpDir/{$id}";
        file_put_contents($src, $code); $tmpFiles[] = $src; $tmpFiles[] = $bin;
        $gcc = findCommand(['gcc']);
        if (!$gcc) { echo json_encode(['success'=>false,'error'=>'GCC compiler not found']); goto cleanup; }
        $compile = runLocal(escapeshellarg($gcc) . " -o " . escapeshellarg($bin) . " " . escapeshellarg($src), '');
        if ($compile['code'] !== 0 || !empty($compile['stderr'])) {
            echo json_encode(['success'=>true,'stdout'=>'','stderr'=>$compile['stderr'],'code'=>$compile['code']]); goto cleanup;
        }
        $result = runLocal(escapeshellarg($bin), $stdin);

    } elseif ($lang === 'java') {
        // Java needs public class name = filename
        preg_match('/public\s+class\s+(\w+)/', $code, $m);
        $className = $m[1] ?? 'Solution';
        $src = "$tmpDir/{$className}.java"; $tmpFiles[] = $src;
        file_put_contents($src, $code);
        $javac = findCommand(['javac']);
        $java  = findCommand(['java']);
        if (!$javac || !$java) { echo json_encode(['success'=>false,'error'=>'Java runtime/compiler not found']); goto cleanup; }
        $compile = runLocal(escapeshellarg($javac) . ' ' . escapeshellarg($src), '');
        if ($compile['code'] !== 0) {
            echo json_encode(['success'=>true,'stdout'=>'','stderr'=>$compile['stderr'],'code'=>$compile['code']]); goto cleanup;
        }
        $tmpFiles[] = "$tmpDir/{$className}.class";
        $result = runLocal(escapeshellarg($java) . ' -cp ' . escapeshellarg($tmpDir) . ' ' . $className, $stdin);

    } elseif ($lang === 'php') {
        $f = "$tmpDir/{$id}.php";
        file_put_contents($f, $code); $tmpFiles[] = $f;
        $php = findCommand(['php']);
        if (!$php) { echo json_encode(['success'=>false,'error'=>'PHP runtime not found']); goto cleanup; }
        $result = runLocal(escapeshellarg($php) . ' ' . escapeshellarg($f), $stdin);

    } elseif ($lang === 'ruby') {
        $ruby = findCommand(['ruby']);
        if (!$ruby) { echo json_encode(['success'=>false,'error'=>'Ruby not installed']); goto cleanup; }
        $f = "$tmpDir/{$id}.rb";
        file_put_contents($f, $code); $tmpFiles[] = $f;
        $result = runLocal("$ruby " . escapeshellarg($f), $stdin);

    } else {
        // Fallback: Piston API for unsupported languages (Go, Rust, Kotlin, etc.)
        $pistonLang = ['go'=>'go','rust'=>'rust','typescript'=>'typescript','kotlin'=>'kotlin','csharp'=>'csharp'][$lang] ?? $lang;
        $payload = json_encode(['language'=>$pistonLang,'version'=>'*',
            'files'=>[['content'=>$code]],'stdin'=>$stdin,'run_timeout'=>10000]);
        $ch = curl_init('https://emkc.org/api/v2/piston/execute');
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,
            CURLOPT_TIMEOUT=>20,CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
            CURLOPT_POSTFIELDS=>$payload]);
        $res  = curl_exec($ch);
        $cerr = curl_error($ch);
        if ($cerr || !$res) { echo json_encode(['success'=>false,'error'=>"Network error: $cerr"]); goto cleanup; }
        $data = json_decode($res, true);
        echo json_encode([
            'success' => true,
            'stdout'  => trim($data['run']['stdout'] ?? ''),
            'stderr'  => trim($data['run']['stderr'] ?? ($data['compile']['stderr'] ?? '')),
            'code'    => $data['run']['code'] ?? 0,
        ]);
        goto cleanup;
    }

    echo json_encode([
        'success' => true,
        'stdout'  => $result['stdout'] ?? '',
        'stderr'  => $result['stderr'] ?? '',
        'code'    => $result['code'] ?? 0,
    ]);

} catch (Throwable $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}

cleanup:
foreach ($tmpFiles as $f) { if (file_exists($f)) @unlink($f); }
