<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use League\OAuth2\Server\CryptKey;
use phpseclib3\Crypt\RSA;
use phpseclib3\File\X509;

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

        // Generate RSA key pair
        $privateKey = RSA::createKey(2048);

        // Create X509 object and set Distinguished Name
        $subject = new X509();
        $subject->setDN([
            "countryName" => "UK",
            "stateOrProvinceName" => "London",
            "localityName" => "London",
            "organizationName" => "Health Data Research UK",
            "organizationalUnitName" => "Gateway",
            "commonName" => "hdruk.ac.uk",
        ]);

        // Create a self-signed certificate
        $issuer = new X509();
        $issuer->setDN($subject->getDN());

        $x509 = new X509();
        $x509->setStartDate('-1 day');
        $x509->setEndDate('+365 days');
        $x509->setSerialNumber(chr(1));

        $result = $x509->sign($issuer, $subject, 'sha256WithRSAEncryption', $privateKey);
        $certificate = $x509->saveX509($result);

        // Save the private key
        $privateKeyString = $privateKey->toString('PKCS8');
        file_put_contents(storage_path('oauth-private.key'), $privateKeyString);
        $this->info('Private key saved to: ' . storage_path('oauth-private.key'));

        // Save the public key (certificate)
        file_put_contents(storage_path('oauth-public.key'), $certificate);
        $this->info('Public key (certificate) saved to: ' . storage_path('oauth-public.key'));

        $this->info('Custom keys generated successfully.');
    }


    // no success in verification with jwks
    // public function handle()
    // {
    //     // Distinguished name (DN) for the certificate
    //     $dn = array(
    //         "countryName" => "UK",
    //         "stateOrProvinceName" => "London",
    //         "localityName" => "London",
    //         "organizationName" => "Health Data Research UK",
    //         "organizationalUnitName" => "Gateway",
    //         "commonName" => "Health Data Research UK",
    //         "emailAddress" => "gateway@hdruk.ac.uk"
    //     );

    //     // Paths for keys and certificate
    //     $privateKeyPath = storage_path('oauth-private.key');
    //     $publicKeyPath = storage_path('oauth-public.key');
    //     $certificatePath = storage_path('oauth-cert.crt');

    //     // Generate a new private (and public) key pair
    //     $privateKeyResource = openssl_pkey_new([
    //         "private_key_bits" => 2048,
    //         "private_key_type" => OPENSSL_KEYTYPE_RSA,
    //     ]);

    //     // Export the private key to a string
    //     openssl_pkey_export($privateKeyResource, $privateKey);

    //     // Save the private key to a file
    //     file_put_contents($privateKeyPath, $privateKey);

    //     // Extract the public key
    //     $keyDetails = openssl_pkey_get_details($privateKeyResource);
    //     $publicKey = $keyDetails['key'];

    //     // Save the public key to a file
    //     file_put_contents($publicKeyPath, $publicKey);

    //     // Generate a self-signed certificate valid for 365 days with SHA-256
    //     $csr = openssl_csr_new($dn, $privateKeyResource, ['digest_alg' => 'sha256']);
    //     $x509 = openssl_csr_sign($csr, null, $privateKeyResource, 365, ['digest_alg' => 'sha256']);

    //     // Export the certificate to a string
    //     openssl_x509_export($x509, $certificate);

    //     // Save the certificate to a file
    //     file_put_contents($certificatePath, $certificate);

    //     // Use CryptKey for compatibility with Laravel Passport
    //     try {
    //         $cryptKey = new CryptKey($privateKeyPath, null, false);  // Pass the private key file path
    //         $this->info("CryptKey successfully initialized.");
    //     } catch (\Exception $e) {
    //         $this->error("Error initializing CryptKey: " . $e->getMessage());
    //         return 1;
    //     }

    //     // Output a success message
    //     $this->info("Private key, public key, and self-signed certificate (SHA-256) generated successfully!");
    //     $this->info("Private Key Path: $privateKeyPath");
    //     $this->info("Public Key Path: $publicKeyPath");
    //     $this->info("Certificate Path: $certificatePath");

    //     return Command::SUCCESS;

    // }
}
