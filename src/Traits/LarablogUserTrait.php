<?php
namespace Gorankrgovic\Larablog\Traits;

use Illuminate\Support\Facades\Config;

/**
 * Created by PhpStorm.
 * Date: 6/26/18
 * Time: 12:44 PM
 * LarablogUserTrait.php
 * @author Goran Krgovic <goran@dashlocal.com>
 */

trait LarablogUserTrait {


    /**
     * Return listings
     * @return mixed
     */
    public function articles()
    {
        return $this->hasMany(
            Config::get('larablog.models.article'),
            Config::get('larablog.foreign_keys.user')
        );
    }

    /**
     * Get articles helper
     *
     * @return mixed
     */
    public function getArticles()
    {
        return $this->articles()->get();
    }

    /**
     * Get paginated articles
     *
     * @param int $paginate
     * @return mixed
     */
    public function getArticlesPaginated($paginate = 10)
    {
        return $this->articles()->paginate($paginate);
    }

}