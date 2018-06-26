<?php
namespace Gorankrgovic\Larablog\Models;

use Gorankrgovic\Larablog\Traits\LarablogArticleTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * Created by PhpStorm.
 * Date: 6/25/18
 * Time: 2:41 PM
 * LarablogArticle.php
 * @author Goran Krgovic <goran@dashlocal.com>
 */

class LarablogArticle extends Model
{
    use LarablogArticleTrait;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table;

    /**
     * Model constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = Config::get('larablog.tables.articles');
    }

}