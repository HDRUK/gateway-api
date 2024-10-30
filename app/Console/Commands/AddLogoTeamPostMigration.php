<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;

class AddLogoTeamPostMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-logo-team-post-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $custodians = [
            [
                'name' => 'alleviate',
                'mongo_object_id' => '613b2174b8b58d157f531e7a',
                'logo' => 'alleviate.jpg',
            ],
            // notfound
            [
                'name' => 'alspac',
                'mongo_object_id' => '',
                'logo' => 'alspac.jpg',
            ],
            [
                'name' => 'BARTS HEALTH',
                'mongo_object_id' => '607db9c2e1f9d3704d570cde',
                'logo' => 'barts-health.jpg',
            ],
            [
                'name' => 'BHF Data Science Centre',
                'mongo_object_id' => '607db9c2e1f9d3704d570ce2',
                'logo' => 'bhf-dsc.png',
            ],
            [
                'name' => 'BRAIN TUMOUR CHARITY',
                'mongo_object_id' => '60a6313d7dd392a16e228a94',
                'logo' => 'brain-tumour-charity.jpg',
            ],
            [
                'name' => 'BREATHE',
                'mongo_object_id' => '607db9c2e1f9d3704d570ce6',
                'logo' => 'breathe.png',
            ],
            [
                'name' => 'BRITISH REGIONAL HEART STUDY - RESEARCH DEPARTMENT OF PRIMARY CARE & POPULATION HEALTH - UCL',
                'mongo_object_id' => '649430ffbbcd17d4962b9b80',
                'logo' => 'british-heart-society.jpg',
            ],
            [
                'name' => 'cancer research horizon',
                'mongo_object_id' => '630793c96d4914bed2157142',
                'logo' => 'cancer-research-horizons.png',
            ],
            // notfound
            [
                'name' => 'chief scientist office',
                'mongo_object_id' => '',
                'logo' => 'chief-scientist-office.jpg',
            ],
            // ??
            [
                'name' => 'COVID-19 GENOMICS UK',
                'mongo_object_id' => '607db9c2e1f9d3704d570cea',
                'logo' => 'cog-uk.jpg',
            ],
            [
                'name' => 'CPRD',
                'mongo_object_id' => '607db9c2e1f9d3704d570cee',
                'logo' => 'cprd.jpg',
            ],
            [
                'name' => 'cystic fibrosis',
                'mongo_object_id' => '607db9c3e1f9d3704d570cf2',
                'logo' => 'cystic-fibrosis.png',
            ],
            // notfound
            [
                'name' => 'dare',
                'mongo_object_id' => '',
                'logo' => 'dare.png',
            ],
            [
                'name' => 'dash',
                'mongo_object_id' => '6202a0fad02da916e629c7e6',
                'logo' => 'dash.jpg',
            ],
            [
                'name' => 'data can',
                'mongo_object_id' => '607db9c3e1f9d3704d570cf6',
                'logo' => 'data-can.jpg',
            ],
            [
                'name' => 'dataloch',
                'mongo_object_id' => '61603974c8c15ee90b768546',
                'logo' => 'dataloch.png',
            ],
            [
                'name' => 'datamind',
                'mongo_object_id' => '61800787f4dde0c2a01e1ea2',
                'logo' => 'datamind.png',
            ],
            [
                'name' => 'discover now',
                'mongo_object_id' => '607db9c3e1f9d3704d570cfa',
                'logo' => 'discover-now.png',
            ],
            [
                'name' => 'dpuk',
                'mongo_object_id' => '64c0f43d4543595392377af3',
                'logo' => 'dpuk.png',
            ],
            [
                'name' => 'enewborn',
                'mongo_object_id' => '651592ed50ab279488a11e5f',
                'logo' => 'enewborn.jpg',
            ],
            [
                'name' => 'eoe',
                'mongo_object_id' => '667d47b8a7e1cf7efecc4600',
                'logo' => 'eoe-sde.png',
            ],
            [
                'name' => 'generation scotland',
                'mongo_object_id' => '607db9c3e1f9d3704d570cfe',
                'logo' => 'generation-scotland.png',
            ],
            // notfound
            [
                'name' => 'generation study',
                'mongo_object_id' => '',
                'logo' => 'generations-study.jpg',
            ],
            [
                'name' => 'genomics england',
                'mongo_object_id' => '607db9c3e1f9d3704d570d02',
                'logo' => 'genomics-england.jpg',
            ],
            // notfound
            [
                'name' => 'gosh',
                'mongo_object_id' => '',
                'logo' => 'gosh.png',
            ],
            // notfound
            [
                'name' => 'gstt',
                'mongo_object_id' => '',
                'logo' => 'gstt.jpg',
            ],
            [
                'name' => 'gut reaction',
                'mongo_object_id' => '607db9c3e1f9d3704d570d0a',
                'logo' => 'gut-reaction.png',
            ],
            [
                'name' => 'hdruk',
                'mongo_object_id' => '5f7b1a2bce9f65e6ed83e7da',
                'logo' => 'hdruk.jpg',
            ],
            [
                'name' => 'health informatics dundee',
                'mongo_object_id' => '607db9c4e1f9d3704d570d1f',
                'logo' => 'health-informatics-dundee.png',
            ],
            [
                'name' => 'hfea',
                'mongo_object_id' => '61c0812b9f8427632e60f6fe',
                'logo' => 'hfea.jpg',
            ],
            [
                'name' => 'hqip',
                'mongo_object_id' => '607db9c4e1f9d3704d570d23',
                'logo' => 'hqip.jpg',
            ],
            [
                'name' => 'hsc',
                'mongo_object_id' => '5f89662f7150a1b050be0710',
                'logo' => 'hsc.png',
            ],
            [
                'name' => 'icnarc',
                'mongo_object_id' => '65a53ae32882b32a90b8eb4b',
                'logo' => 'icnarc.png',
            ],
            [
                'name' => 'icoda',
                'mongo_object_id' => '6441799bdb7d6005bd9ac2e6',
                'logo' => 'icoda.png',
            ],
            [
                'name' => 'imperial college london',
                'mongo_object_id' => '607db9c4e1f9d3704d570d27',
                'logo' => 'imperial-college-london.jpg',
            ],
            [
                'name' => 'insight',
                'mongo_object_id' => '5f8437a3b7dec2a83e8cceba',
                'logo' => 'insight.png',
            ],
            [
                'name' => 'kms sde',
                'mongo_object_id' => '66966d9644c85f56690c345d',
                'logo' => 'kms-sde.png',
            ],
            [
                'name' => 'manchester university nhs',
                'mongo_object_id' => '63d3f83fcfcd30ad858a6619',
                'logo' => 'manchester-university-nhs-ft.png',
            ],
            [
                'name' => 'mireda',
                'mongo_object_id' => '65411b41bf97d64a3699352a',
                'logo' => 'mireda.jpg',
            ],
            [
                'name' => 'national neonatal research database',
                'mongo_object_id' => '6048a783a89bdc3443823333',
                'logo' => 'national-neonatal-research-database.jpg',
            ],
            [
                'name' => 'nhs barth health',
                'mongo_object_id' => '607db9c2e1f9d3704d570cde',
                'logo' => 'nhs-barts-health.jpg',
            ],
            // notfound
            [
                'name' => 'nhs birmingham',
                'mongo_object_id' => '',
                'logo' => 'nhs-birmingham.jpg',
            ],
            [
                'name' => 'nhs digital',
                'mongo_object_id' => '5f86cd34980f41c6f02261f4',
                'logo' => 'nhs-digital.jpg',
            ],
            [
                'name' => 'nhs england',
                'mongo_object_id' => '6427fbba72aa1325df67a776',
                'logo' => 'nhs-england.png',
            ],
            [
                'name' => 'nhs oxford university hospital',
                'mongo_object_id' => '6048a940a89bdc3443823356',
                'logo' => 'nhs-oxford-university-hospitals.jpg',
            ],
            // notfound
            [
                'name' => 'nhs scotland',
                'mongo_object_id' => '',
                'logo' => 'nhs-scotland.jpg',
            ],
            // notfound
            [
                'name' => 'nhs wales',
                'mongo_object_id' => '',
                'logo' => 'nhs-wales.jpg',
            ],
            [
                'name' => 'nhsx',
                'mongo_object_id' => '607db9c4e1f9d3704d570d3b',
                'logo' => 'nhsx.jpg',
            ],
            [
                'name' => 'nihr bioresource',
                'mongo_object_id' => '607db9c4e1f9d3704d570d3f',
                'logo' => 'nihr-bioresource.jpeg',
            ],
            // notfound : multiple nihr
            [
                'name' => 'nihr national institute',
                'mongo_object_id' => '',
                'logo' => 'nihr-national-institute.jpg',
            ],
            [
                'name' => 'nihr national institute',
                'mongo_object_id' => '',
                'logo' => 'nihr-national-institute.png',
            ],
            [
                'name' => 'nshd mrc',
                'mongo_object_id' => '63f8dfc27b3caf7af3283816',
                'logo' => 'nshd-mrc.jpg',
            ],
            [
                'name' => 'ons',
                'mongo_object_id' => '5fc12be363eaab9e68dae76e',
                'logo' => 'ons.png',
            ],
            [
                'name' => 'our future health',
                'mongo_object_id' => '651c153089f7f431c48fc21b',
                'logo' => 'our-future-health.jpg',
            ],
            [
                'name' => 'oxford university hospitals',
                'mongo_object_id' => '6048a940a89bdc3443823356',
                'logo' => 'oxford-university-hospitals.jpg',
            ],
            [
                'name' => 'parkinsons uk',
                'mongo_object_id' => '60a630c77dd392a16e228a92',
                'logo' => 'parkinsons-uk.png',
            ],
            [
                'name' => 'pathlake',
                'mongo_object_id' => '6486eaa91e9c1e0bf8ccf1b3',
                'logo' => 'pathlake.png',
            ],
            [
                'name' => 'pioneer',
                'mongo_object_id' => '607db9c5e1f9d3704d570d5f',
                'logo' => 'pioneer.png',
            ],
            // notfound
            [
                'name' => 'public health england',
                'mongo_object_id' => '',
                'logo' => 'public-health-england.jpg',
            ],
            [
                'name' => 'public health scotland',
                'mongo_object_id' => '5f8992a97150a1b050be0712',
                'logo' => 'public-health-scotland.png',
            ],
            [
                'name' => 'qresearch',
                'mongo_object_id' => '61ba059ac688870b12a4e05b',
                'logo' => 'qresearch.jpg',
            ],
            [
                'name' => 'renal association',
                'mongo_object_id' => '609bc283781cac1dbab2a385',
                'logo' => 'renal-association.jpg',
            ],
            [
                'name' => 'royal college of practitioner',
                'mongo_object_id' => '607db9c5e1f9d3704d570d6b',
                'logo' => 'royal-college-of-practitioners.jpg',
            ],
            [
                'name' => 'royal marsden',
                'mongo_object_id' => '627156d028bc5cdbf03cf8e4',
                'logo' => 'royal-marsden.jpg',
            ],
            [
                'name' => 'sail',
                'mongo_object_id' => '5f3f98068af2ef61552e1d75',
                'logo' => 'sail.png',
            ],
            // notfound
            [
                'name' => 'uk serp',
                'mongo_object_id' => '',
                'logo' => 'serp.png',
            ],
            // notfound
            [
                'name' => 'south london and maudsley nhs foundation trust',
                'mongo_object_id' => '',
                'logo' => 'south-london-and-maudsley-nhs-foundation-trust.jpg',
            ],
            [
                'name' => 'the brain tumor charity.',
                'mongo_object_id' => '60a6313d7dd392a16e228a94',
                'logo' => 'the-brain-tumor-charity.png',
            ],
            [
                'name' => 'tissue directory coordination centre',
                'mongo_object_id' => '607db9c6e1f9d3704d570d77',
                'logo' => 'tissue-directory-coordination-centre.jpg',
            ],
            [
                'name' => 'uk biobank',
                'mongo_object_id' => '607db9c6e1f9d3704d570d7f',
                'logo' => 'uk-biobank.png',
            ],
            [
                'name' => 'uk llc',
                'mongo_object_id' => '607db9c6e1f9d3704d570d83',
                'logo' => 'uk-llc.jpg',
            ],
            [
                'name' => 'ukhsa',
                'mongo_object_id' => '607db9c5e1f9d3704d570d63',
                'logo' => 'ukhsa.jpg',
            ],
            [
                'name' => 'university hospitals leicester',
                'mongo_object_id' => '607db9c6e1f9d3704d570d8b',
                'logo' => 'university-hospitals-leicester.jpg',
            ],
            [
                'name' => 'university of nottingham',
                'mongo_object_id' => '607db9c6e1f9d3704d570d93',
                'logo' => 'university-of-nottingham.jpg',
            ],
            [
                'name' => 'wessex sde',
                'mongo_object_id' => '65fc124b40134905bb6ef0bd',
                'logo' => 'wessex-sde.png',
            ],
            [
                'name' => 'west of scotland safe haven',
                'mongo_object_id' => '65279cb1a4a5b147bb6dad11',
                'logo' => 'west-of-scotland-safe-haven.jpg',
            ],
            [
                'name' => 'GREAT ORMOND STREET HOSPITAL',
                'mongo_object_id' => '607db9c3e1f9d3704d570d06',
                'logo' => 'gosh.png',
            ],
        ];

        Team::query()->update([
            'team_logo' => null,
        ]);

        foreach ($custodians as $custodian) {
            if (!$custodian['mongo_object_id']) {
                continue;
            }

            $team = Team::where([
                'mongo_object_id' => $custodian['mongo_object_id'],
            ])->first();

            if ($team) {
                $logoUrl = '/teams/' . $custodian['logo'];
                Team::where([
                    'mongo_object_id' => $custodian['mongo_object_id'],
                ])->update([
                    'team_logo' => $logoUrl,
                ]);

                $this->info('Logo updated for team ' . $custodian['name'] . ' with ' . $logoUrl);
            } else {
                $this->warn('Team with mongo id ' . $custodian['mongo_object_id'] . ' not found');
            }
        }
    }
}
