<?php

declare(strict_types=1);

namespace DndPortal\Migration;

final class AccountMigrationPlanner {
    /** @return array{base:string, username:string} */
    public static function usernameFor(int $legacyId, string $name): array {
        $base = strtolower(
            trim(preg_replace('/[^a-z0-9]+/i', '-', self::transliterate($name)), '-'),
        );
        if ($base === '') {
            $base = 'user';
        }
        $base = substr($base, 0, 55);
        return ['base' => $base, 'username' => $base . '-' . $legacyId];
    }

    /** @param array<int, array{legacy_id:int,name:string,email:string}> $rows */
    public static function propose(array $rows, int $firstNewId = 1): array {
        usort($rows, static fn(array $a, array $b): int => $a['legacy_id'] <=> $b['legacy_id']);
        $result = [];
        foreach ($rows as $index => $row) {
            $name = self::usernameFor($row['legacy_id'], $row['name']);
            $result[] = [
                'legacy_id' => $row['legacy_id'],
                'proposed_new_id' => $firstNewId + $index,
                'username' => $name['username'],
                'email' => $row['email'],
            ];
        }
        return $result;
    }

    /** @param array<int, array{legacy_id:int,username:string}> $plan */
    public static function conflicts(
        array $plan,
        array $existingEmails,
        array $existingUsernames,
    ): array {
        $emailSeen = [];
        $usernameSeen = [];
        $conflicts = [];
        foreach ($plan as $row) {
            foreach (
                [
                    ['email', strtolower($row['email'] ?? '')],
                    ['username', strtolower($row['username'])],
                ]
                as [$field, $value]
            ) {
                if ($value === '') {
                    continue;
                }
                $seen = $field === 'email' ? $emailSeen : $usernameSeen;
                if (
                    isset($seen[$value]) ||
                    in_array(
                        $value,
                        $field === 'email' ? $existingEmails : $existingUsernames,
                        true,
                    )
                ) {
                    $conflicts[] = ['legacy_id' => $row['legacy_id'], 'field' => $field];
                }
                if ($field === 'email') {
                    $emailSeen[$value] = true;
                } else {
                    $usernameSeen[$value] = true;
                }
            }
        }
        return $conflicts;
    }

    private static function transliterate(string $value): string {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        return $converted === false ? $value : $converted;
    }
}
