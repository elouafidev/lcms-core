<?php

declare(strict_types=1);

namespace App\Core\Security\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

class TotpService
{
    private Google2FA $google2fa;

    public function __construct(?Google2FA $google2fa = null)
    {
        $this->google2fa = $google2fa ?? new Google2FA;
    }

    /**
     * Generate a new base32 secret key.
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Get the QR code URL (otpauth format).
     */
    public function getQrCodeUrl(string $company, string $holder, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl($company, $holder, $secret);
    }

    /**
     * Generate inline SVG representation of the QR code.
     */
    public function getQrCodeSvg(string $company, string $holder, string $secret, int $size = 200): string
    {
        $qrCodeUrl = $this->getQrCodeUrl($company, $holder, $secret);

        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);

        return $writer->writeString($qrCodeUrl);
    }

    /**
     * Verify a 6-digit TOTP code against the secret.
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $sanitizedCode = preg_replace('/\s+/', '', $code) ?? '';

        if (strlen($sanitizedCode) !== 6 || ! ctype_digit($sanitizedCode)) {
            return false;
        }

        $result = $this->google2fa->verifyKey($secret, $sanitizedCode, $window);

        return $result !== false;
    }
}
