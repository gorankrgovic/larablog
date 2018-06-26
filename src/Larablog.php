<?php
namespace Gorankrgovic\Larablog;

/**
 * Created by PhpStorm.
 * Date: 6/25/18
 * Time: 1:50 PM
 * Larablog.php
 * @author Goran Krgovic <goran@dashlocal.com>
 */

use Illuminate\Support\Facades\Config;

/**
 * Class Larablog
 * @package Gorankrgovic\Larablog
 */
class Larablog
{

    /**
     * Laravel application.
     *
     * @var \Illuminate\Foundation\Application
     */
    public $app;


    /**
     * Larablog constructor.
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }


    /**
     * If slug exists
     *
     * @param $slug
     * @return mixed
     */
    protected function checkIfArticleSlugExists($slug)
    {
        $model = Config::get('larablog.models.article');
        $res = $model::where('slug', $slug);
        return $res->exists();
    }

    /**
     * Create the article slug
     *
     * @param $slug
     * @param int $n
     * @return string
     */
    public function createArticleSlug($slug, $n=0)
    {
        $slug = str_slug( $slug );
        $_slug  = $slug . ( $n > 0 ? '-' . $n : '' );
        // short circuit a call when the original name is valid
        if ( $this->checkIfArticleSlugExists($_slug) )
        {
            return $this->createArticleSlug($slug, $n+1);
        }
        return $_slug;
    }

    /**
     * @param $slug
     * @return string
     */
    public function createSlugFromTitle($slug)
    {
        return str_slug($slug);
    }

    /**
     * Create the slug
     *
     * @param $slug
     * @return string
     */
    public function slug($slug)
    {
        $slug = $this->createSlugFromTitle($slug);
        return $this->createArticleSlug($slug);
    }


    /**
     * Generate the excerpt helper
     *
     * @param $content
     * @return string
     */
    public function excerpt($content)
    {
        return ( str_limit(strip_tags($content), Config::get('larablog.excerpt_length')) );
    }

    /**
     * Auto paragraph if needed
     *
     * @param $content
     * @return string
     */
    public function autop($content)
    {
        return ContentFormatting::autop($content);
    }

    /**
     * Sanitize the title (create a custom slug)
     *
     * @param $title
     * @return string
     */
    public function sanitize_title($title)
    {
        return ContentFormatting::sanitize_title($title);
    }



}