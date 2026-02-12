<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class MfaService extends BaseService
{
    private const SECRET_LENGTH = 32;
    private const BACKUP_CODE_COUNT = 8;
    private const TOTP_WINDOW = 1; // Allow 1 period before/after for clock drift
    private const TOTP_PERIOD = 30; // seconds
    private const TOTP_DIGITS = 6;

    /**
     * Generate MFA setup data (secret + QR code provisioning URI).
     *
     * @return array{secret: string, qr_uri: string}
     */
    public function setup(User $user): array
    {
        if ($user->mfa_enabled) {
            throw ValidationException::withMessages([
                'mfa' => ['MFA is already enabled.'],
            ]);
        }

        $secret = $this->generateSecret();

        // Store secret temporarily (not enabled yet)
        $user->update(['mfa_secret' => encrypt($secret)]);

        $issuer = config('app.name', 'BizSocials');
        $qrUri = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=%d&period=%d',
            urlencode($issuer),
            urlencode($user->email),
            $secret,
            urlencode($issuer),
            self::TOTP_DIGITS,
            self::TOTP_PERIOD,
        );

        $this->log('MFA setup initiated', ['user_id' => $user->id]);

        return [
            'secret' => $secret,
            'qr_uri' => $qrUri,
        ];
    }

    /**
     * Verify the TOTP code during setup and enable MFA.
     *
     * @return array{backup_codes: array<string>}
     */
    public function verifySetup(User $user, string $code): array
    {
        if ($user->mfa_enabled) {
            throw ValidationException::withMessages([
                'mfa' => ['MFA is already enabled.'],
            ]);
        }

        if ($user->mfa_secret === null) {
            throw ValidationException::withMessages([
                'mfa' => ['MFA setup has not been initiated.'],
            ]);
        }

        $secret = decrypt($user->mfa_secret);

        if (!$this->verifyTotp($secret, $code)) {
            throw ValidationException::withMessages([
                'code' => ['Invalid verification code.'],
            ]);
        }

        // Generate backup codes
        $backupCodes = $this->generateBackupCodes();

        $user->update([
            'mfa_enabled' => true,
            'settings' => array_merge($user->settings ?? [], [
                'mfa_backup_codes' => array_map(fn ($c) => bcrypt($c), $backupCodes),
            ]),
        ]);

        $this->log('MFA enabled', ['user_id' => $user->id]);

        return ['backup_codes' => $backupCodes];
    }

    /**
     * Verify a TOTP code during login.
     */
    public function verifyLogin(User $user, string $code): bool
    {
        if (!$user->mfa_enabled || $user->mfa_secret === null) {
            return true; // MFA not enabled, skip verification
        }

        $secret = decrypt($user->mfa_secret);

        // Try TOTP first
        if ($this->verifyTotp($secret, $code)) {
            return true;
        }

        // Try backup codes
        return $this->verifyBackupCode($user, $code);
    }

    /**
     * Disable MFA for a user.
     */
    public function disable(User $user, string $password): void
    {
        if (!$user->mfa_enabled) {
            throw ValidationException::withMessages([
                'mfa' => ['MFA is not enabled.'],
            ]);
        }

        if (!password_verify($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Invalid password.'],
            ]);
        }

        $user->update([
            'mfa_enabled' => false,
            'mfa_secret' => null,
        ]);

        // Remove backup codes from settings
        $settings = $user->settings ?? [];
        unset($settings['mfa_backup_codes']);
        $user->update(['settings' => $settings]);

        $this->log('MFA disabled', ['user_id' => $user->id]);
    }

    /**
     * Get MFA status for a user.
     *
     * @return array{enabled: bool, setup_pending: bool}
     */
    public function status(User $user): array
    {
        return [
            'enabled' => (bool) $user->mfa_enabled,
            'setup_pending' => !$user->mfa_enabled && $user->mfa_secret !== null,
        ];
    }

    /**
     * Generate a base32-encoded TOTP secret.
     */
    private function generateSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < self::SECRET_LENGTH; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }

        return $secret;
    }

    /**
     * Generate backup codes.
     *
     * @return array<string>
     */
    private function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::BACKUP_CODE_COUNT; $i++) {
            $codes[] = strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        }

        return $codes;
    }

    /**
     * Verify a TOTP code against the secret.
     */
    private function verifyTotp(string $secret, string $code): bool
    {
        $currentTimestamp = (int) floor(time() / self::TOTP_PERIOD);

        for ($i = -self::TOTP_WINDOW; $i <= self::TOTP_WINDOW; $i++) {
            $expectedCode = $this->generateTotp($secret, $currentTimestamp + $i);
            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a TOTP code for a given timestamp counter.
     */
    private function generateTotp(string $secret, int $counter): string
    {
        $binaryKey = $this->base32Decode($secret);
        $binaryCounter = pack('N*', 0) . pack('N*', $counter);

        $hash = hash_hmac('sha1', $binaryCounter, $binaryKey, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $otp = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::TOTP_DIGITS);

        return str_pad((string) $otp, self::TOTP_DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Decode a base32-encoded string.
     */
    private function base32Decode(string $input): string
    {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper(rtrim($input, '='));
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $val = strpos($map, $input[$i]);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }

    /**
     * Verify a backup code and consume it if valid.
     */
    private function verifyBackupCode(User $user, string $code): bool
    {
        $settings = $user->settings ?? [];
        $hashedCodes = $settings['mfa_backup_codes'] ?? [];

        foreach ($hashedCodes as $index => $hashedCode) {
            if (password_verify($code, $hashedCode)) {
                // Remove used backup code
                unset($hashedCodes[$index]);
                $settings['mfa_backup_codes'] = array_values($hashedCodes);
                $user->update(['settings' => $settings]);

                $this->log('Backup code used', ['user_id' => $user->id]);

                return true;
            }
        }

        return false;
    }
}
