<?php

namespace App\Console\Commands;

use Mauro;
use Auditor;

use App\Models\SchemaProfileChecksum;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SchemaToMauroProfileUpdater extends Command
{
    /**
     * The internal name for this command, for use
     * mainly in logging mechanisms
     * 
     * @var string
     */
    private $tag = 'SchemaToMauroProfileUpdater';

    /**
     * The internal user id for this command. Denotes an
     * internal service and thus no 'real' user account
     * 
     * @var int
     */
    private $simulatedUserId = 2;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:schema-to-mauro-profile-updater';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nightly process to checksum latest schema version
        from github, and create a new Mauro profile version on detecting
        changes.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $checksums = SchemaProfileChecksum::firstOrFail();
        // Should only ever be one, as it's indended to recycle
        // the entry
        if ($checksums) {
            $schema = $this->callGitHub();
            if ($schema !== null) {
                $hash = sha1($schema);
                if ($hash !== $checksums->checksum) {
                    $checksums->checksum = $hash;
                    $checksums->save();

                    // Update Mauro Profile with new version of schema
                    Auditor::log(
                        $this->simulatedUserId,
                        'Creating new mauro profile version, after detecting changes (old: ' . $checksums->checksum . ' / new: ' . $hash . ')',
                        $this->tag
                    );
                } else {
                    Auditor::log(
                        $this->simulatedUserId,
                        'No version change detected - aborting',
                        $this->tag
                    );
                }
            }
        } else {
            // Create a new instance of a schema checksum for the first run
            $schema = $this->callGitHub();
            if ($schema !== null) {
                $hash = sha1($schema);

                SchemaProfileChecksum::create([
                    'checksum' => $hash,
                ]);

                // Update Mauro Profile with this new schema
                Auditor::log(
                    $this->simulatedUserId,
                    'Updating mauro profile version, no existing version detected with checksum ' . $hash,
                    $this->tag
                );
            }
        }
    }

    /**
     * Calls GitHub schemata-2 repo for the latest version of the
     * GWDM schema.
     * 
     * @return string
     */
    private function callGitHub(): string
    {
        $response = Http::acceptJson()->get(env('GWDM_SCHEMA_REPO'));
        if ($response->status() === 200) {
            return json_encode($response->json());
        } 

        Auditor::log(
            $this->simulatedUserId,
            'Unable to retrieve schema from GitHub',
            $this->tag
        );
        return null;
    }
}
