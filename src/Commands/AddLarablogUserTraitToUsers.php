<?php
namespace Gorankrgovic\Larablog\Commands;

use Traitor\Traitor;
use Gorankrgovic\Larablog\Traits\LarablogUserTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;



/**
 * Created by PhpStorm.
 * Date: 6/26/18
 * Time: 12:51 PM
 * AddLarablogUserTraitToUsers.php
 * @author Goran Krgovic <goran@dashlocal.com>
 */

class AddLarablogUserTraitToUsers extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'larablog:add-trait';

    /**
     * Trait added to User model
     *
     * @var string
     */
    protected $targetTrait = LarablogUserTrait::class;



    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $models = $this->getUserModels() ;
        foreach ($models as $model) {
            if (!class_exists($model)) {
                $this->error("Class $model does not exist.");
                return;
            }
            if ($this->alreadyUsesLarablogUserTrait($model)) {
                $this->error("Class $model already uses LarablogUserTrait.");
                continue;
            }
            Traitor::addTrait($this->targetTrait)->toClass($model);
        }
        $this->info("LarablogUserTrait added successfully to {$models->implode(', ')}");
    }

    /**
     * Check if the class already uses trait.
     *
     * @param  string  $model
     * @return bool
     */
    protected function alreadyUsesLarablogUserTrait($model)
    {
        return in_array(LarablogUserTrait::class, class_uses($model));
    }


    /**
     * Get the description of which clases the trait was added.
     *
     * @return string
     */
    public function getDescription()
    {
        return "Add LarablogUserTrait to {$this->getUserModels()->implode(', ')} class";
    }



    /**
     * Return the User models array.
     *
     * @return array
     */
    protected function getUserModels()
    {
        return new Collection(Config::get('larablog.user_models', []));
    }

}