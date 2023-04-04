<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Publisher;

class PublisherTest extends TestCase
{
   /**
   * Test that a new Publisher is an instance of the class
   *
   * @return void
   */
   public function test_new_publisher_is_instance_of_publisher()
   {
      $this->assertInstanceOf(Publisher::class, new Publisher());
   }
}

?>
