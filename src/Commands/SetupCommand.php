<?php
namespace Gorankrgovic\Larablog\Commands;


use Illuminate\Console\Command;

class SetupCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'larablog:setup';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup models and migrations for Larablog.';


    /**
     * Commands to call with their description.
     *
     * @var array
     */
    protected $calls = [
        'larablog:migrate' => 'Migrate the tables',
        'larablog:setup-models' => 'Setup the models',
        'larablog:add-trait' => 'Add the trait',
    ];

    /**
     * Create a new command instance
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->calls as $command => $info) {
            $this->line(PHP_EOL . $info);
            $this->call($command);
        }
    }
}