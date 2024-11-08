<?php

use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the conversions.
     *
     * @return void
     */
    public function up()
    {
        echo 'this is a test for conversion for up() method' . PHP_EOL;
    }

    /**
     * Reverse the conversions.
     *
     * @return void
     */
    public function down()
    {
        echo 'this is a test for conversion for down() method' . PHP_EOL;
    }
};
