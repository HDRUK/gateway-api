<?php

namespace Tests\Feature;

use App\Models\License;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Tests\Traits\MockExternalApis;

class UpdateLicensesTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    public function test_update_licenses_command_handles_success_response()
    {
        $licenseInit = License::where([
            'code' => 'CATOSL_1_1',
        ])->first();

        $this->assertTrue(!((bool) $licenseInit), 'CATOSL_1_1 not found before in LICENSE');

        Http::fake([
            'https://data.europa.eu/api/hub/search/vocabularies/licence' => Http::response($this->mockEuLicenseApiResponse(), 200),
            'http://publications.europa.eu/resource/authority/licence/*' => Http::response($this->mockLicenseDetails(), 200)
        ]);

        $this->artisan('app:update-eu-licenses')->assertExitCode(0);

        $licenseAfter = License::where([
            'code' => 'CATOSL_1_1',
        ])->first();

        $this->assertTrue((bool) $licenseAfter, 'CATOSL_1_1 found after in LICENSE');
    }

    private function mockEuLicenseApiResponse()
    {
        return [
            "result" => [
                "index" => "vocabulary",
                "count" => 1,
                "results" => [
                    [
                        "pref_label" => [
                            "en" => "Computer Associates Trusted Open Source License 1.1 (CATOSL-1.1)"
                        ],
                        "extensions" => null,
                        "alt_label" => null,
                        "resource" => "http://publications.europa.eu/resource/authority/licence/CATOSL_1_1",
                        "id" => "CATOSL_1_1",
                        "in_scheme" => [
                            "http://publications.europa.eu/resource/authority/licence"
                        ]
                    ]
                ]
            ]
        ];
    }

    private function mockLicenseDetails()
    {
        return '<?xml version="1.0" encoding="utf-8" ?>
            <rdf:RDF
                xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
                xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
                xmlns:dcterms="http://purl.org/dc/terms/"
                xmlns:owl="http://www.w3.org/2002/07/owl#"
                xmlns:skos="http://www.w3.org/2004/02/skos/core#"
                xmlns:ns5="http://publications.europa.eu/ontology/euvoc#"
                xmlns:ns6="http://lemon-model.net/lemon#"
                xmlns:ns7="http://www.w3.org/2008/05/skos-xl#"
                xmlns:foaf="http://xmlns.com/foaf/0.1/"
                xmlns:dc="http://purl.org/dc/elements/1.1/"
                xmlns:ns10="http://publications.europa.eu/ontology/authority/"
                xmlns:ns11="http://publications.europa.eu/resource/authority/"
                xmlns:ns12="http://creativecommons.org/ns#"
                xmlns:ns13="http://data.europa.eu/eli/ontology#" >
                <rdf:Description rdf:about="http://publications.europa.eu/resource/authority/licence">
                    <skos:hasTopConcept rdf:resource="http://publications.europa.eu/resource/authority/licence/CATOSL_1_1" />
                </rdf:Description>
                <rdf:Description rdf:about="http://publications.europa.eu/resource/authority/licence/CATOSL_1_1">
                    <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept" />
                    <rdf:type rdf:resource="http://publications.europa.eu/ontology/euvoc#Licence" />
                    <dcterms:created rdf:datatype="http://www.w3.org/2001/XMLSchema#date">2020-04-01</dcterms:created>
                    <owl:versionInfo>20230927-0</owl:versionInfo>
                    <skos:inScheme rdf:resource="http://publications.europa.eu/resource/authority/licence" />
                    <skos:prefLabel xml:lang="en">Computer Associates Trusted Open Source License 1.1 (CATOSL-1.1)</skos:prefLabel>
                    <ns5:startDate rdf:datatype="http://www.w3.org/2001/XMLSchema#date">2004-07-31</ns5:startDate>
                    <ns6:context rdf:resource="http://publications.europa.eu/resource/authority/use-context/DCAT_AP" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/df11276ec313f956d51414358ee73b3b" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/80ed848f9cf2720924cde96a74be359b" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/e684f9c4b99c494a7ee59df2913409c7" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/e7bd1b6f4a100d728b14b9a7160fb52b" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/dab6091bef50e17c1dda918f4f0dbb66" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/4c2b5f8af298cd4a525d872b0b54c6db" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/46db7bce17e2008cd19e794be3f7f316" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/2a14b3cb6aef7e41467ff857ecaad0e3" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/aee6a93d8c1465e117b833ea04c7a2b7" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/a5ee902046658310a610d969059f27c2" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/a9031ee711116acf159bb9c66b5a3a19" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/bf8c412f9153ad0e4c8620c1e57816b2" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/262d412adfb8058e8ada82439e48e408" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/d72a513bd4c46c23e6f96d23184c286a" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/267da9737f5da33525a8cba277353018" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/3b2573a2a08a2061ebc10b0992bfb58d" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/73985a48095a0f25611d3c109840800b" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/8b10d2675c80500451ba952862ce2649" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/a14dc827f8a1ae97a02bf816d1aad28b" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/5a1334470e62ede82b4bda7778e8e09c" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/44f839da584906ba1a848bab1da74a97" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/8288cc60ae1a36ad5743c5762b97b471" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/df59e290621697cfe227a6b169b8c861" />
                    <ns7:altLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/4d903cb07194fc906f399f59a186380e" />
                    <ns7:prefLabel rdf:resource="http://publications.europa.eu/resource/authority/licence/b551035e91625491332b8ec52b72c11a" />
                    <dcterms:dateAccepted rdf:datatype="http://www.w3.org/2001/XMLSchema#date">2020-06-04</dcterms:dateAccepted>
                    <dcterms:dateSubmitted rdf:datatype="http://www.w3.org/2001/XMLSchema#date">2020-05-19</dcterms:dateSubmitted>
                    <skos:topConceptOf rdf:resource="http://publications.europa.eu/resource/authority/licence" />
                    <ns5:status rdf:resource="http://publications.europa.eu/resource/authority/concept-status/CURRENT" />
                    <ns5:xlDefinition rdf:resource="http://publications.europa.eu/resource/authority/licence/e37397a0c2614001b7597cf616d84b4a" />
                    <foaf:homepage rdf:resource="https://spdx.org/licenses/CATOSL-1.1.html" />
                    <skos:altLabel xml:lang="da">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="sk">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="ro">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="et">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="bg">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="pl">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="de">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="hu">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="sv">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="es">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="hr">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="nl">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="pt">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="fr">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="fi">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="ga">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="it">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="cs">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="en">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="lt">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="mt">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="lv">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="el">CATOSL-1.1</skos:altLabel>
                    <skos:altLabel xml:lang="sl">CATOSL-1.1</skos:altLabel>
                    <skos:definition xml:lang="en">CATOSL-1.1 is a copyleft-style, free software licence approved by the Open Source Initiative. Under this licence, it is possible to create a larger work by combining the program with other software code not governed by the terms of this licence, and distribute the larger work as a single product.</skos:definition>
                    <skos:changeNote xml:lang="en">The source of the start-use date: https://webcache.googleusercontent.com/search?q=cache:7pbd65y1OUYJ:https://www.theinquirer.net/inquirer/news/1022762/computer-associates-open-source-guru-speaks-out+&amp;cd=1&amp;hl=en&amp;ct=clnk&amp;gl=lu</skos:changeNote>
                    <skos:exactMatch rdf:resource="https://opensource.org/licenses/CATOSL-1.1" />
                    <dc:identifier>CATOSL_1_1</dc:identifier>
                    <ns10:authority-code>CATOSL_1_1</ns10:authority-code>
                    <ns10:deprecated>false</ns10:deprecated>
                    <ns10:op-code>CATOSL_1_1</ns10:op-code>
                    <ns10:start.use>2004-07-31</ns10:start.use>
                    <ns11:op-code>CATOSL_1_1</ns11:op-code>
                    <ns12:requires rdf:resource="http://creativecommons.org/ns#LesserCopyleft" />
                    <dcterms:references rdf:resource="https://opensource.org/licenses/CATOSL-1.1" />
                    <ns5:appliesTo rdf:resource="http://publications.europa.eu/resource/authority/licence-domain/CODE" />
                    <ns5:licenceVersion>1.1</ns5:licenceVersion>
                    <ns13:responsibility_of>CA Technologies (former Computer Associates International, Inc.)</ns13:responsibility_of>
                    <ns5:xlChangeNote rdf:resource="http://publications.europa.eu/resource/authority/licence/2e767002c797dc378a66007e1d6bfc7f" />
                    <ns5:context rdf:resource="http://publications.europa.eu/resource/authority/use-context/DCAT_AP" />
                </rdf:Description>
            </rdf:RDF>'
        ;
    }
}
