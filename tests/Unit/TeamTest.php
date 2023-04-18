<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Team;

class TeamTest extends TestCase
{
   /**
   * Test that a new Team is an instance of the class
   *
   * @return void
   */
   public function test_new_publisher_is_instance_of_publisher()
   {
      $this->assertInstanceOf(Team::class, new Team());
   }
}

?>
