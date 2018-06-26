<?php
namespace Gorankrgovic\Larablog\Traits;

use Illuminate\Support\Facades\Config;

/**
 * Created by PhpStorm.
 * Date: 6/25/18
 * Time: 2:42 PM
 * LarablogCategoryTrait.php
 * @author Goran Krgovic <goran@dashlocal.com>
 */

trait LarablogCategoryTrait
{


    /**
     * Morphed by many
     * @param $relationship
     * @return mixed
     */
    public function getMorphByRelation($relationship)
    {
        return $this->morphedByMany(
            Config::get('larablog.models')[$relationship],
            'node',
            Config::get('larablog.tables.category_article'),
            Config::get('larablog.foreign_keys.category'),
            'node_id'
        );
    }


    /**
     * Return the associated properties for this category
     *
     * @return mixed
     */
    public function associatedArticles()
    {
        return $this->getMorphByRelation('article');
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (!preg_match('/^can[A-Z].*/', $method)) {
            return parent::__call($method, $parameters);
        }
    }
}
