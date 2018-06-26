<?php
namespace Gorankrgovic\Larablog\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * Class Larablog
 * @package Gorankrgovic\Larablog\Facades
 */
class Larablog extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'larablog';
    }



}