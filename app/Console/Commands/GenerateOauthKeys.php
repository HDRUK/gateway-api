<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateOauthKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-oauth-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate RSA private and public keys and store them in the storage folder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating custom RS256');

        $configargs = [
            "digest_alg"       => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        $dn = [
            "countryName" => "UK",
            "stateOrProvinceName" => "London",
            "localityName" => "London",
            "organizationName" => "Health Data Research UK",
            "organizationalUnitName" => "Gateway",
            "commonName" => "hdruk.ac.uk",
        ];

        // Generate a new private and public key pair
        $privateKeyResource = openssl_pkey_new($configargs);

        if ($privateKeyResource === false) {
            $this->error('Failed to generate private key.');
            $this->error(openssl_error_string());
            return 1;
        }

        // Generate a certificate signing request (CSR)
        $csrResource = openssl_csr_new($dn, $privateKeyResource, $configargs);

        if ($csrResource === false) {
            $this->error('Failed to generate CSR.');
            $this->error(openssl_error_string());
            return 1;
        }

        // Generate a self-signed certificate
        $x509Resource = openssl_csr_sign($csrResource, null, $privateKeyResource, 365, $configargs);

        if ($x509Resource === false) {
            $this->error('Failed to generate self-signed certificate.');
            $this->error(openssl_error_string());
            return 1;
        }

        // Export the private key in PKCS#1 format
        $privateKeyPem = '';
        if (!openssl_pkey_export($privateKeyResource, $privateKeyPem, null, [
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'encrypt_key'      => false,
        ])) {
            $this->error('Failed to export private key in PKCS#1 format.');
            $this->error(openssl_error_string());
            return 1;
        }

        // Get the public key from the certificate
        $publicKeyResource = openssl_pkey_get_public($x509Resource);

        if ($publicKeyResource === false) {
            $this->error('Failed to get public key from certificate.');
            $this->error(openssl_error_string());
            return 1;
        }

        // Extract the public key details
        $keyDetails = openssl_pkey_get_details($publicKeyResource);

        if ($keyDetails === false) {
            $this->error('Failed to get public key details.');
            $this->error(openssl_error_string());
            return 1;
        }

        // Extract the public key in PEM format
        $publicKeyPem = $keyDetails['key'];

        // Save the keys to the storage
        $privateKeyPath = storage_path('oauth-private.key');
        $publicKeyPath  = storage_path('oauth-public.key');

        if (File::put($privateKeyPath, $privateKeyPem) === false) {
            $this->error('Failed to write private key to file.');
            return 1;
        }

        if (File::put($publicKeyPath, $publicKeyPem) === false) {
            $this->error('Failed to write public key to file.');
            return 1;
        }

        // Set the correct permissions
        @chmod($privateKeyPath, 0600);
        @chmod($publicKeyPath, 0644);

        $this->info('RSA keys generated successfully with DN included.');

        return 0;
    }
}
