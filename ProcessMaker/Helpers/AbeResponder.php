<?php

namespace ProcessMaker\Helpers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\User;

/**
 * Builds the abe_responder request-data object from an ABE click.
 */
class AbeResponder
{
    /**
     * @return array<string, mixed>
     */
    public static function fromRequest(Request $request, string $tokenAbe): array
    {
        $signed = AbeRecipientSignature::verify($request, $tokenAbe);
        $actedAt = Carbon::now()->toIso8601String();
        $userAgent = (string) $request->userAgent();

        $responder = [
            'acted_at' => $actedAt,
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'device' => self::detectDevice($userAgent),
            'email_client' => self::detectEmailClient($userAgent),
        ];

        if ($signed) {
            $responder['email'] = $signed['email'];
            $responder['user_id'] = $signed['user_id'] ?: null;
            $responder['groups'] = $signed['groups'];
        }

        $authUser = self::authenticatedUser();
        if ($authUser) {
            $responder['authenticated_email'] = $authUser->email;
            $responder['authenticated_user_id'] = $authUser->id;

            $signedEmail = strtolower(trim((string) ($responder['email'] ?? '')));
            $authEmail = strtolower(trim((string) $authUser->email));
            $responder['identity_mismatch'] = $signedEmail !== '' && $signedEmail !== $authEmail;
        }

        return $responder;
    }

    private static function authenticatedUser(): ?User
    {
        if (!Auth::check()) {
            return null;
        }

        $user = Auth::user();
        if (!$user instanceof User) {
            return null;
        }

        // Anonymous session used for public ABE clicks is not a real identity
        if (($user->username ?? null) === AnonymousUser::ANONYMOUS_USERNAME) {
            return null;
        }

        return $user;
    }

    private static function detectDevice(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        if ($ua === '') {
            return 'unknown';
        }
        if (preg_match('/mobile|android|iphone|ipad|ipod|blackberry|windows phone/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private static function detectEmailClient(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        if ($ua === '') {
            return 'Unknown';
        }
        if (str_contains($ua, 'googleimageproxy') || str_contains($ua, 'gmail')) {
            return 'Gmail';
        }
        if (str_contains($ua, 'outlook') || str_contains($ua, 'microsoft office') || str_contains($ua, 'ms-office')) {
            return 'Outlook';
        }
        if (str_contains($ua, 'thunderbird')) {
            return 'Thunderbird';
        }
        if (str_contains($ua, 'yahoo')) {
            return 'Yahoo Mail';
        }
        if (str_contains($ua, 'applewebkit') && (str_contains($ua, 'macintosh') || str_contains($ua, 'iphone') || str_contains($ua, 'ipad'))) {
            // Apple Mail / iOS Mail often look like Safari; coarse best-effort
            if (!str_contains($ua, 'crios') && !str_contains($ua, 'fxios') && !str_contains($ua, 'edgios')) {
                return 'Apple Mail';
            }
        }

        return 'Unknown';
    }
}
