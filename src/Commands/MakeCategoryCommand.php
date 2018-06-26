<?php
namespace Gorankrgovic\Larablog\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Config;

/**
 * Created by PhpStorm.
 * Date: 6/25/18
 * Time: 3:09 PM
 * MakeArticleCommand.php
 * @author Goran Krgovic <goran@dashlocal.com>
 */

class MakeCategoryCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'larablog:category';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Category model if it does not exist';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Category model';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__. '/../../stubs/category.stub';
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return Config::get('larablog.models.category', 'Category');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

}