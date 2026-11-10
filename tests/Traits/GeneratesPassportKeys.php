<?php

namespace Tests\Traits;

use RuntimeException;

/**
 * Supplies the RSA keypair Passport signs OIDC tokens with.
 *
 * The real keys live in storage/*.key, which .gitignore excludes - they
 * are generated per environment by `passport:keys`. Tests must therefore
 * never read them: they are present on a developer machine and absent in
 * CI, which makes any test that depends on them pass locally and fail on
 * the pipeline.
 *
 * A throwaway keypair is generated instead, once per process, so these
 * tests behave identically everywhere. Nothing here verifies against a
 * real key; the tests only need a well-formed RSA pair.
 */
trait GeneratesPassportKeys
{
    /** @var array{private: string, public: string}|null */
    private static ?array $generatedPassportKeys = null;

    protected function usePassportKeys(): void
    {
        if (self::$generatedPassportKeys === null) {
            self::$generatedPassportKeys = $this->generateKeyPair();
        }

        config([
            'passport.private_key' => self::$generatedPassportKeys['private'],
            'passport.public_key' => self::$generatedPassportKeys['public'],
        ]);
    }

    /**
     * @return array{private: string, public: string}
     */
    private function generateKeyPair(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($resource === false) {
            throw new RuntimeException('Could not generate an RSA keypair for the test run.');
        }

        openssl_pkey_export($resource, $privateKey);

        $details = openssl_pkey_get_details($resource);

        if ($details === false || !isset($details['key'])) {
            throw new RuntimeException('Could not read the generated public key.');
        }

        return ['private' => $privateKey, 'public' => $details['key']];
    }
}
