<?php
namespace Gorankrgovic\Larablog\Traits;

/**
 * Created by PhpStorm.
 * Date: 6/25/18
 * Time: 3:02 PM
 * LarablogHasEventsTrait.php
 * @author Goran Krgovic <goran@dashlocal.com>
 */

/**
 * Trait LarablogHasEventsTrait
 * @package Gorankrgovic\Larablog\Traits
 */
trait LarablogHasEventsTrait
{
    /**
     * Register an observer to the Larablog events.
     *
     * @param  object|string  $class
     * @return void
     */
    public static function propellerObserve($class)
    {
        $observables = [
            'categoryAttached',
            'categoryDetached',
            'categorySynced'
        ];

        $className = is_string($class) ? $class : get_class($class);

        foreach ( $observables as $event )
        {
            if ( method_exists($class, $event) ) {
                static::registerLarablogEvent(\Illuminate\Support\Str::snake($event, '.'), $className . '@' . $event);
            }
        }
    }

    /**
     * Fire the given event for the model.
     *
     * @param  string  $event
     * @param  array  $payload
     * @return mixed
     */
    protected function fireLarablogEvent($event, array $payload)
    {
        if ( !isset(static::$dispatcher ) )
        {
            return true;
        }

        return static::$dispatcher->fire(
            "larablog.{$event}: ".static::class,
            $payload
        );
    }

    /**
     * Register a larablog event with the dispatcher.
     *
     * @param  string  $event
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function registerLarablogEvent($event, $callback)
    {
        if (isset(static::$dispatcher)) {
            $name = static::class;

            static::$dispatcher->listen("larablog.{$event}: {$name}", $callback);
        }
    }

    public static function categoryAttached($callback)
    {
        static::registerLarablogEvent('category.attached', $callback);
    }

    public static function categoryDetached($callback)
    {
        static::registerLarablogEvent('category.detached', $callback);
    }

    public static function categorySynced($callback)
    {
        static::registerLarablogEvent('category.synced', $callback);
    }
}