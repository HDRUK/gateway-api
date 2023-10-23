<?php

namespace App\Console\Commands;

use Mauro;
use Auditor;

use App\Models\Team;
use App\Models\User;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\SchemaProfileChecksum;

class SchemaToMauroProfileUpdater extends Command
{
    /**
     * The internal name for this command, for use
     * mainly in logging mechanisms
     * 
     * @var string
     */
    private $tag = 'SchemaToMauroProfileUpdater';

    private $simulatedUserId = 2;
    private $simulatedTeamId = -99;
    private $actionType = 'UNKNOWN';
    private $actionService = NULL;

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
    public function handle(): int
    {
        $this->actionService = $this->tag;

        $checksums = SchemaProfileChecksum::first();
        // Should only ever be one, as it's indended to recycle
        // the entry
        if ($checksums) {
            $schema = $this->callGitHub();
            if ($schema !== '') {
                $hash = sha1($schema);
                if ($hash !== $checksums->checksum) {
                    $checksums->checksum = $hash;
                    $checksums->save();

                    // Update Mauro Profile with new version of schema
                    Auditor::log(
                        $this->simulatedUserId,
                        $this->simulatedTeamId,
                        $this->actionType,
                        $this->actionService,
                        'Updating mauro profile version, after detecting changes (old: ' . $checksums->checksum . ' / new: ' . $hash . ')'
                    );

                    // TODO: Question here - are we intending to 'update' a profile, or just create anew?
                } else {
                    Auditor::log(
                        $this->simulatedUserId,
                        $this->simulatedTeamId,
                        $this->actionType,
                        $this->actionService,
                        'No version change detected - aborting'
                    );

                    return 0;
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
                    $this->simulatedTeamId,
                    $this->actionType,
                    $this->actionService,
                    'Creating mauro profile version, no existing version detected with checksum ' . $hash
                );

                $mauroResponse = Mauro::createDataStandard('HDRUK Gateway Data Model', 
                    'Gateway Data Model common format',
                    'Health Data Research UK',
                    'Health Data Research UK',
                    env('MAURO_PARENT_FOLDER_ID'),
                    json_decode($schema, true)
                );

                if ($mauroResponse['DataModel']['responseStatus'] === 201) {
                    return 0;
                }
            }
        }

        // Failure
        return -1;
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
            $this->simulatedTeamId,
            $this->actionType,
            $this->actionService,
            'Unable to retrieve schema from GitHub'
        );

        return '';
    }
}
