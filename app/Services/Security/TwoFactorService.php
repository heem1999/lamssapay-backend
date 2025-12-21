<?php

namespace App\Services\Security;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Cache;

class TwoFactorService
{
    protected $google2fa;

    public function __construct()
    {
        // Assuming pragmarx/google2fa is installed via composer
        // If not, this would need to be installed: composer require pragmarx/google2fa
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a new 2FA secret for a user.
     *
     * @param User $user
     * @return array
     */
    public function generateSecret(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();
        
        // Generate QR Code URL
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl
        ];
    }

    /**
     * Verify the provided 2FA code against the user's secret.
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    public function verify(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            return false;
        }

        return $this->google2fa->verifyKey($user->two_factor_secret, $code);
    }

    /**
     * Verify a code against a specific secret (used during setup).
     *
     * @param string $secret
     * @param string $code
     * @return bool
     */
    public function verifyWithSecret(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }
}
