<?php

namespace Tests\Feature;

use App\Models\CustomerSatisfaction;
use Tests\TestCase;

class CustomerSatisfactionTest extends TestCase
{
    /**
     * Test creating a CustomerSatisfaction entry.
     *
     * @return void
     */
    public function test_create_customer_satisfaction()
    {

        $score = 5;

        $customerSatisfaction = CustomerSatisfaction::create([
            'score' => $score
        ]);

        $this->assertDatabaseHas('customer_satisfactions', [
            'score' => $score,
        ]);

        $this->assertEquals($score, $customerSatisfaction->score);
    }

    /**
     * Test if a CustomerSatisfaction instance is saved and retrieved properly.
     *
     * @return void
     */
    public function test_customer_satisfaction_instance()
    {
        $score = 4;
        $customerSatisfaction = CustomerSatisfaction::create(['score' => $score]);

        $retrieved = CustomerSatisfaction::find($customerSatisfaction->id);

        $this->assertNotNull($retrieved);
        $this->assertEquals($score, $retrieved->score);
    }
}
