<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;

class MakeConversionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:conversion {name : The name of the conversion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new conversion file';

    /**
     * The Filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();

        $this->files = $files;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get the name of the conversion
        $name = trim($this->argument('name'));

        // Generate the class name
        $className = $this->getClassName($name);

        // Generate the file path
        $filePath = $this->getFilePath($name);

        // Build the class content
        $classContent = $this->buildClass($className);

        // Create the conversion file
        if ($this->files->exists($filePath)) {
            $this->error("Conversion {$className} already exists!");
            return 1;
        }

        $this->makeDirectory($filePath);

        $this->files->put($filePath, $classContent);

        $this->info("Created Conversion: " . basename($filePath));

        // Dump the autoloads
        $this->composer->dumpAutoloads();

        return 0;
    }

    /**
     * Get the class name from the conversion name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getClassName($name)
    {
        return Str::studly($name);
    }

    /**
     * Get the file path for the conversion.
     *
     * @param  string  $name
     * @return string
     */
    protected function getFilePath($name)
    {
        $timestamp = date('Y_m_d_His');
        $fileName = $timestamp . '_' . $name . '.php';
        return database_path('conversions') . '/' . $fileName;
    }

    /**
     * Build the class content.
     *
     * @param  string  $className
     * @return string
     */
    protected function buildClass($className)
    {
        return <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    /**
     * Run the conversions.
     *
     * @return void
     */
    public function up()
    {
        //
    }

    /**
     * Reverse the conversions.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};

EOT;
    }

    /**
     * Create the directory for the conversion file if it doesn't exist.
     *
     * @param  string  $path
     * @return void
     */
    protected function makeDirectory($path)
    {
        $directory = dirname($path);

        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true, true);
        }
    }
}
