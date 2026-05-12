<?php

declare(strict_types=1);

namespace Sk\SmartId\Validation;

/**
 * Rejects certificates that look like end-entity certs from being used as
 * trust anchors. phpseclib's X509::loadCA() does NOT enforce the RFC 5280
 * requirement that a trust anchor have basicConstraints CA:TRUE, so we
 * must enforce it ourselves before any cert reaches the trust store or
 * the validator's chain-validation step.
 *
 * Without this guard, an RP that mis-installs an end-entity cert in its
 * trust store (config mistake, supply-chain tampering of the trust bundle,
 * etc.) silently promotes that cert's private-key holder to a CA — and
 * the validator will accept Smart-ID-shaped certs self-issued from it.
 */
final class CaCertificateGuard
{
    /**
     * @throws \InvalidArgumentException if the PEM is not a CA certificate
     */
    public static function assertIsCaCertificate(string $pemCertificate): void
    {
        $parsed = @openssl_x509_parse($pemCertificate);
        if ($parsed === false) {
            throw new \InvalidArgumentException('Trust anchor PEM is not a parseable X.509 certificate');
        }

        $basicConstraints = $parsed['extensions']['basicConstraints'] ?? '';
        if (!is_string($basicConstraints) || stripos($basicConstraints, 'CA:TRUE') === false) {
            throw new \InvalidArgumentException(
                'Trust anchor certificate is missing basicConstraints CA:TRUE — '
                . 'end-entity certificates must not be used as trust anchors',
            );
        }

        $keyUsage = $parsed['extensions']['keyUsage'] ?? '';
        if (!is_string($keyUsage) || stripos($keyUsage, 'Certificate Sign') === false) {
            throw new \InvalidArgumentException(
                'Trust anchor certificate is missing keyUsage keyCertSign — '
                . 'cannot be used as a CA',
            );
        }
    }
}
