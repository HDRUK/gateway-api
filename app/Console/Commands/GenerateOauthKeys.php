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

        $args = [
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

        // Generate a new private key
        $privateKeyResource = openssl_pkey_new($args);

        if (!$privateKeyResource) {
            $this->error('Failed to generate private key.');
            return 1;
        }

        // Generate a certificate signing request
        $csr = openssl_csr_new($dn, $privateKeyResource, $args);

        if (!$csr) {
            $this->error('Failed to generate CSR.');
            return 1;
        }

        // Generate a self-signed certificate valid for 365 days
        $x509 = openssl_csr_sign($csr, null, $privateKeyResource, 365, $args);

        if (!$x509) {
            $this->error('Failed to generate self-signed certificate.');
            return 1;
        }

        // Export the private key
        openssl_pkey_export($privateKeyResource, $privateKey);

        // Extract the public key from the private key resource
        $keyDetails  = openssl_pkey_get_details($privateKeyResource);
        $publicKeyPem = $keyDetails['key'];

        // Save the keys to the storage
        $privateKeyPath = storage_path('oauth-private.key');
        $publicKeyPath  = storage_path('oauth-public.key');

        File::put($privateKeyPath, $privateKey);
        File::put($publicKeyPath, $publicKeyPem);

        // Set the correct permissions
        @chmod($privateKeyPath, 0600);
        @chmod($publicKeyPath, 0644);

        $this->info('RSA keys generated successfully.');

        return 0;
    }
}
