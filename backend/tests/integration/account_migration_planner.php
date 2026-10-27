<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/Migration/AccountMigrationPlanner.php';
use DndPortal\Migration\AccountMigrationPlanner;

$ok = static function (bool $condition, string $label): void {
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    } echo "PASS: $label\n";
};
$rows = [
    ['legacy_id' => 7, 'name' => 'Zoë van Dijk', 'email' => 'z@example.test'],
    ['legacy_id' => 2, 'name' => 'Alex Example', 'email' => 'a@example.test'],
];
$plan = AccountMigrationPlanner::propose($rows, 11);
$ok(array_column($plan, 'legacy_id') === [2, 7], 'legacy IDs sorted deterministically');
$ok(array_column($plan, 'proposed_new_id') === [11, 12], 'new IDs assigned deterministically');
$ok(str_ends_with($plan[0]['username'], '-2') && str_ends_with($plan[1]['username'], '-7') && count(array_unique(array_column($plan, 'username'))) === 2, 'usernames deterministic and unique');
$ok(count(AccountMigrationPlanner::conflicts($plan, [], [])) === 0, 'clean target has no conflicts');
$ok(count(AccountMigrationPlanner::conflicts($plan, ['a@example.test'], [])) === 1, 'email conflict is explicit');
$ok(count(AccountMigrationPlanner::conflicts($plan, [], ['alex-example-2'])) === 1, 'username conflict is explicit');
$ok(AccountMigrationPlanner::propose($rows, 11) === $plan, 'repeat planning is identical');
$ok(password_verify('Synthetic-Migration-Password-2026!', password_hash('Synthetic-Migration-Password-2026!', PASSWORD_BCRYPT)), 'current verifier accepts a known bcrypt hash');
echo "PASS: account migration planner\n";
