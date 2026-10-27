<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/Config.php';
require dirname(__DIR__, 2) . '/src/Database/Connection.php';
use DndPortal\Config;
use DndPortal\Database\Connection;

Config::loadDotEnv(dirname(__DIR__, 2) . '/.env');
if (Config::env('APP_ENV') !== 'testing' || !preg_match('/^dnd_portal_test_run_[0-9]+$/', (string) Config::env('DB_NAME'))) {
    exit("REFUSED\n");
}
$db = Connection::make();
if (($argv[1] ?? '') === 'worker') {
    [$oid,$kind,$amount,$ref,$barrier] = array_slice($argv, 2);
    file_put_contents($barrier . '.ready.' . getmypid(), getmypid());
    while (count(glob($barrier . '.ready.*')) < 2) {
        usleep(10000);
    }
    try {
        $db->beginTransaction();
        $q = $db->prepare('SELECT
    amount_cents
FROM
    payment_obligations
WHERE
    id = ? FOR UPDATE');
        $q->execute([(int) $oid]);
        $ob = $q->fetch();
        $q = $db->prepare('SELECT COALESCE(SUM(CASE WHEN entry_kind="payment" THEN amount_cents ELSE -amount_cents END),0) FROM payment_entries WHERE obligation_id=? AND status="confirmed"');
        $q->execute([(int) $oid]);
        $net = (int) $q->fetchColumn();
        if ($kind === 'reversal' && $amount > $net) {
            throw new RuntimeException('reversal-bound');
        }
        $q = $db->prepare('INSERT INTO payment_entries(obligation_id,amount_cents,currency,status,entry_kind,external_reference) VALUES(?,? ,"EUR","confirmed",?,?)');
        $q->execute([(int) $oid,(int) $amount,$kind,$ref]);
        $db->commit();
        echo "success\n";
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo "rejected:" . $e->getMessage() . "\n";
    }
    exit;
}
$q = $db->prepare("INSERT INTO campaigns(name,slug,type,created_by) VALUES('Concurrency payment',?,'campaign',1)");
$q->execute(['concurrency-' . bin2hex(random_bytes(3))]);
$campaign = (int) $db->lastInsertId();
$q = $db->prepare('INSERT INTO payment_requests(
    campaign_id,
    created_by,
    title,
    amount_cents,
    currency
)
VALUES(?, 1, "Concurrency", 10000, "EUR")');
$q->execute([$campaign]);
$rid = (int) $db->lastInsertId();
$q = $db->prepare('INSERT INTO payment_obligations(
    payment_request_id,
    user_id,
    amount_cents,
    currency
)
VALUES(?, 1, 10000, "EUR")');
$q->execute([$rid]);
$oid = (int) $db->lastInsertId();
$db->prepare('INSERT INTO payment_entries(obligation_id,amount_cents,currency,status,entry_kind,external_reference) VALUES(?,10000,"EUR","confirmed","payment","seed")')->execute([$oid]);
$run = function (string $kind, int $amount, string $ref) use ($oid): array {
    $barrier = tempnam(sys_get_temp_dir(), 'pay-race-');
    @unlink($barrier);
    $cmd = 'php ' . escapeshellarg(__FILE__) . ' worker ' . (int) $oid . ' ' . escapeshellarg($kind) . ' ' . $amount . ' ' . escapeshellarg($ref) . ' ' . escapeshellarg($barrier);
    $a = proc_open($cmd, [1 => ['pipe','w'],2 => ['pipe','w']], $x);
    $b = proc_open($cmd, [1 => ['pipe','w'],2 => ['pipe','w']], $y);
    $oa = trim(stream_get_contents($x[1]));
    $ob = trim(stream_get_contents($y[1]));
    foreach ([$x,$y] as $pipes) {
        foreach ($pipes as $p) {
            fclose($p);
        }
    }
    proc_close($a);
    proc_close($b);
    foreach (glob($barrier . '.ready.*') as $f) {
        @unlink($f);
    }
    return [$oa, $ob];
};
$runMixed = function () use ($oid): array {
    $barrier = tempnam(sys_get_temp_dir(), 'pay-mixed-');
    @unlink($barrier);
    $make = function (string $kind, int $amount, string $ref) use ($oid, $barrier) {
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' worker ' . (int) $oid . ' ' . escapeshellarg($kind) . ' ' . $amount . ' ' . escapeshellarg($ref) . ' ' . escapeshellarg($barrier);
        $pipes = [];
        $process = proc_open($cmd, [1 => ['pipe','w'],2 => ['pipe','w']], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Could not start mixed concurrency worker');
        }
        return [$process, $pipes];
    };
    [$a,$ap] = $make('payment', 1000, 'mixed-payment');
    [$b,$bp] = $make('reversal', 5000, 'mixed-reversal');
    $oa = trim(stream_get_contents($ap[1]));
    $ob = trim(stream_get_contents($bp[1]));
    foreach ([$ap,$bp] as $pipes) {
        foreach ($pipes as $p) {
            fclose($p);
        }
    }
    proc_close($a);
    proc_close($b);
    foreach (glob($barrier . '.ready.*') as $f) {
        @unlink($f);
    }
    return [$oa, $ob];
};
[$a,$b] = $run('payment', 100, 'same-ref');
$q = $db->prepare('SELECT
    COUNT(*)
FROM
    payment_entries
WHERE
    obligation_id = ? AND external_reference = ?');
$q->execute([$oid,'same-ref']);
if ((int) $q->fetchColumn() !== 1) {
    throw new RuntimeException('duplicate reference race inserted more than once');
}
echo "PASS: concurrent duplicate reference [$a|$b]\n";
[$a,$b] = $run('reversal', 6000, 'reversal-a');
$q = $db->prepare('SELECT
    COALESCE(SUM(amount_cents),
    0)
FROM
    payment_entries
WHERE
    obligation_id = ? AND entry_kind = "reversal"');
$q->execute([$oid]);
if ((int) $q->fetchColumn() > 10000) {
    throw new RuntimeException('reversal race exceeded original payment');
}
echo "PASS: concurrent reversals bounded [$a|$b]\n";
[$a,$b] = $runMixed();
$q = $db->prepare('SELECT COALESCE(SUM(CASE WHEN entry_kind="payment" THEN amount_cents WHEN entry_kind="reversal" THEN -amount_cents ELSE 0 END),0) FROM payment_entries WHERE obligation_id=? AND status="confirmed"');
$q->execute([$oid]);
$net = (int) $q->fetchColumn();
if ($net < 0 || $net > 10000) {
    throw new RuntimeException('mixed payment/correction race produced invalid net balance');
}
echo "PASS: concurrent payment and correction consistent [$a|$b] net=$net\n";
echo "PASS: payment concurrency database invariants\n";
