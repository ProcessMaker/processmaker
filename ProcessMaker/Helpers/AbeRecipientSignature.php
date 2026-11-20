<?php

namespace ProcessMaker\Helpers;

use Illuminate\Http\Request;

/**
 * Signs and verifies personalized ABE recipient query parameters.
 *
 * Payload: recipient email, user_id, and assigned group(s) for the task.
 */
class AbeRecipientSignature
{
    public const EMAIL = 'abe_email';

    public const USER_ID = 'abe_user_id';

    public const GROUPS = 'abe_groups';

    public const SIG = 'abe_sig';

    /**
     * Build a signed query string to append to ABE action URLs.
     *
     * @param  array<int, array{id:int|string, name?:string}>  $groups
     */
    public static function queryString(string $tokenAbe, string $email, int|string|null $userId, array $groups = []): string
    {
        $normalizedGroups = self::normalizeGroups($groups);
        $params = [
            self::EMAIL => strtolower(trim($email)),
            self::USER_ID => (string) (int) $userId,
            self::GROUPS => self::encodeGroups($normalizedGroups),
        ];
        $params[self::SIG] = self::sign($tokenAbe, $params[self::EMAIL], $params[self::USER_ID], $normalizedGroups);

        return http_build_query($params);
    }

    /**
     * Verify signed params from the request. Returns null if missing/invalid.
     *
     * @return array{email:string,user_id:int,groups:array}|null
     */
    public static function verify(Request $request, string $tokenAbe): ?array
    {
        $email = strtolower(trim((string) $request->query(self::EMAIL, '')));
        $userId = (string) $request->query(self::USER_ID, '');
        $groupsEncoded = (string) $request->query(self::GROUPS, '');
        $sig = (string) $request->query(self::SIG, '');

        if ($email === '' || $sig === '') {
            return null;
        }

        $groups = self::decodeGroups($groupsEncoded);
        $expected = self::sign($tokenAbe, $email, $userId, $groups);

        if (!hash_equals($expected, $sig)) {
            return null;
        }

        return [
            'email' => $email,
            'user_id' => (int) $userId,
            'groups' => $groups,
        ];
    }

    /**
     * @param  array<int, array{id:int|string, name?:string}>  $groups
     */
    private static function sign(string $tokenAbe, string $email, string $userId, array $groups): string
    {
        $canonical = implode('|', [
            $tokenAbe,
            strtolower(trim($email)),
            (string) (int) $userId,
            self::encodeGroups($groups),
        ]);

        return hash_hmac('sha256', $canonical, (string) config('app.key'));
    }

    /**
     * @param  array<int, array{id:int|string, name?:string}>  $groups
     * @return array<int, array{id:int, name:string}>
     */
    private static function normalizeGroups(array $groups): array
    {
        $normalized = [];
        foreach ($groups as $group) {
            if (!is_array($group) || !isset($group['id'])) {
                continue;
            }
            $normalized[] = [
                'id' => (int) $group['id'],
                'name' => (string) ($group['name'] ?? ''),
            ];
        }

        usort($normalized, static fn ($a, $b) => $a['id'] <=> $b['id']);

        return $normalized;
    }

    /**
     * @param  array<int, array{id:int, name:string}>  $groups
     */
    private static function encodeGroups(array $groups): string
    {
        return base64_encode(json_encode(array_values($groups)));
    }

    /**
     * @return array<int, array{id:int, name:string}>
     */
    private static function decodeGroups(string $encoded): array
    {
        if ($encoded === '') {
            return [];
        }

        $decoded = json_decode(base64_decode($encoded, true) ?: '', true);
        if (!is_array($decoded)) {
            return [];
        }

        return self::normalizeGroups($decoded);
    }
}
