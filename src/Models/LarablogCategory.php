<?php
namespace Gorankrgovic\Larablog\Models;

use Gorankrgovic\Larablog\Traits\LarablogCategoryTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * Created by PhpStorm.
 * Date: 6/25/18
 * Time: 2:41 PM
 * LarablogCategory.php
 * @author Goran Krgovic <goran@dashlocal.com>
 */

class LarablogCategory extends Model
{
    use LarablogCategoryTrait;

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
        $this->table = Config::get('larablog.tables.categories');
    }

}