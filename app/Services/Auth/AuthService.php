<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Security\TwoFactorService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthService
{
    protected $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Register a new user.
     *
     * @param array $data
     * @return User
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => 'user', // Default role
                'status' => 'active',
            ]);

            // Create default wallet for the user
            $user->wallets()->create([
                'currency' => 'USD',
                'balance' => 0,
                'is_primary' => true,
            ]);

            return $user;
        });
    }

    /**
     * Authenticate a user.
     *
     * @param string $email
     * @param string $password
     * @param string|null $device_name
     * @return array
     * @throws ValidationException
     */
    public function login(string $email, string $password, ?string $device_name = null): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->two_factor_enabled) {
            return [
                'two_factor_required' => true,
                'user_id' => $user->id, // In a real app, return a temporary signed token for 2FA verification
            ];
        }

        $token = $user->createToken($device_name ?? 'Unknown Device')->plainTextToken;

        // Update last login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        return [
            'two_factor_required' => false,
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Verify 2FA and complete login.
     *
     * @param User $user
     * @param string $code
     * @param string|null $device_name
     * @return array
     * @throws ValidationException
     */
    public function verifyTwoFactorLogin(User $user, string $code, ?string $device_name = null): array
    {
        if (! $this->twoFactorService->verify($user, $code)) {
            throw ValidationException::withMessages([
                'code' => ['The provided two-factor authentication code is invalid.'],
            ]);
        }

        $token = $user->createToken($device_name ?? 'Unknown Device')->plainTextToken;

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Enable 2FA for a user.
     *
     * @param User $user
     * @return array
     */
    public function enableTwoFactor(User $user): array
    {
        return $this->twoFactorService->generateSecret($user);
    }

    /**
     * Confirm and activate 2FA.
     *
     * @param User $user
     * @param string $secret
     * @param string $code
     * @return void
     * @throws ValidationException
     */
    public function confirmTwoFactor(User $user, string $secret, string $code): void
    {
        if (! $this->twoFactorService->verifyWithSecret($secret, $code)) {
            throw ValidationException::withMessages([
                'code' => ['The provided code is invalid.'],
            ]);
        }

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
        ]);
    }

    /**
     * Disable 2FA.
     *
     * @param User $user
     * @return void
     */
    public function disableTwoFactor(User $user): void
    {
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
        ]);
    }
}
