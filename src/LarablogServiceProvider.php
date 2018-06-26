<?php
namespace Gorankrgovic\Larablog;


use Illuminate\Support\ServiceProvider;

/**
 * Created by PhpStorm.
 * Date: 6/25/18
 * Time: 1:58 PM
 * LarablogServiceProvider.php
 * @author Goran Krgovic <goran@dashlocal.com>
 */


/**
 * Class LarablogServiceProvider
 * @package Gorankrgovic\Larablog
 */
class LarablogServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;


    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'Migration' => 'command.larablog.migration',
        'MakeArticle' => 'command.larablog.article',
        'MakeCategory' => 'command.larablog.category',
        'SetupModels' => 'command.larablog.setup-models',
        'Setup' => 'command.larablog.setup',
        'Seeder' => 'command.larablog.seeder',
        'AddLarablogUserTrait' => 'command.larablog.add-trait',
    ];


    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Merge config file for the current app
        $this->mergeConfigFrom(__DIR__.'/../config/larablog.php', 'larablog');

        // Publish the config files
        $this->publishes([
            __DIR__.'/../config/larablog.php' => config_path('larablog.php')
        ], 'larablog');
    }



    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        // Register the app
        $this->registerLarablog();

        // Register Commands
        $this->registerCommands();
    }


    /**
     * Register the application bindings.
     *
     * @return void
     */
    private function registerLarablog()
    {
        $this->app->bind('larablog', function ($app) {
            return new Larablog($app);
        });

        $this->app->alias('larablog', 'Gorankrgovic\Larablog');
    }

    /**
     * Register the given commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        foreach (array_keys($this->commands) as $command) {
            $method = "register{$command}Command";
            call_user_func_array([$this, $method], []);
        }
        $this->commands(array_values($this->commands));
    }

    /*
     * Setup and stuff
     */

    protected function registerMigrationCommand()
    {
        $this->app->singleton('command.larablog.migration', function () {
            return new \Gorankrgovic\Larablog\Commands\MigrationCommand();
        });
    }

    protected function registerMakeArticleCommand()
    {
        $this->app->singleton('command.larablog.article', function ($app) {
            return new  \Gorankrgovic\Larablog\Commands\MakeArticleCommand($app['files']);
        });
    }

    protected function registerMakeCategoryCommand()
    {
        $this->app->singleton('command.larablog.category', function ($app) {
            return new  \Gorankrgovic\Larablog\Commands\MakeCategoryCommand($app['files']);
        });
    }

    protected function registerSetupModelsCommand()
    {
        $this->app->singleton('command.larablog.setup-models', function () {
            return new \Gorankrgovic\Larablog\Commands\SetupModelsCommand();
        });
    }

    protected function registerSetupCommand()
    {
        $this->app->singleton('command.larablog.setup', function () {
            return new \Gorankrgovic\Larablog\Commands\SetupCommand();
        });
    }

    protected function registerSeederCommand()
    {
        $this->app->singleton('command.larablog.seeder', function () {
            return new \Gorankrgovic\Larablog\Commands\SeederCommand();
        });
    }

    protected function registerAddLarablogUserTraitCommand()
    {
        $this->app->singleton('command.larablog.add-trait', function () {
            return new \Gorankrgovic\Larablog\Commands\AddLarablogUserTraitToUsers();
        });
    }

    /**
     * Get the services provided.
     *
     * @return array
     */
    public function provides()
    {
        return array_values($this->commands);
    }

}