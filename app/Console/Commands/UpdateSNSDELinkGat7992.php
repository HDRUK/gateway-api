<?php

namespace App\Console\Commands;

use App\Models\DataProviderColl;
use Exception;
use Illuminate\Console\Command;

class UpdateSNSDELinkGat7992 extends Command
{
    /**
     * * The name and signature of the console command. *
     * * @var string */

    protected $signature = 'app:update-snsde-custodian-network-link';
    /** * The console command description. * * @var string */ protected $description = 'Update link of SNSDE';
    /** * Execute the console command. */ public function handle()
    {
        $data = ['summary' => "The NHS Research Secure Data Environment (SDE) Network helps approved researchers by simplifying and accelerating secure access to the health and social care data they need. " . "It is designed to be the default route for accessing NHS data for research and will drive federation and interoperability in two main ways:" . "\n- aggregating data at a scale much larger than previously possible across the NHS, reducing data silos and landscape complexity. " . "\n- embedding interoperability and enabling federated analytics across multiple platforms – including genomics, imaging, and Electronic Health Records. " . "\n\n### National coverage" . "\nThe SDE Network includes the NHS England SDE and 11 regional SDE teams, covering all of England through a federated approach. " . "\n\n### Rapid access" . "\nBy using the Health Data Research Gateway as its ‘front door’, the SDE Network aims to de-fragment health data assets and make searching for data easier for researchers. The SDE Network provides rapid access to data that has both breadth and depth across care settings and data types (multi-modality), including reliably uniting primary and secondary care datasets. " . "\n\n### Supporting research" . "\nThe NHS Research SDE Network can support research use cases from AI development and epidemiology to health systems research and real-world studies. It is a foundational layer to a strong, data and evidence-based, research and innovation ecosystem. " . "\n\n### Find out more" . "\nInformation about the services provided by members of the NHS Research SDE Network is available [here](https://digital.nhs.uk/data-and-information/research-powered-by-data).", ];
        try {
            $snsde = DataProviderColl::where('name', 'like', '%SDE%')->first();
            if ($snsde) {
                $snsde->summary = $data['summary'];
                $snsde->update();
            } else {
                $this->warn('SNSDE custodian network not found');
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
}
