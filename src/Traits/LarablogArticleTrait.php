<?php
namespace Gorankrgovic\Larablog\Traits;

use Gorankrgovic\Larablog\LarablogHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

/**
 * Created by PhpStorm.
 * Date: 6/25/18
 * Time: 2:43 PM
 * LarablogArticleTrait.php
 * @author Goran Krgovic <goran@dashlocal.com>
 */

trait LarablogArticleTrait
{

    use LarablogHasEventsTrait;

    /**
     * Boots the property model and attaches event listener to remove the many-to-many records when trying to
     * delete. Will NOT delete any records if the property model uses soft deletes.
     *
     *
     * @return void|bool
     */
    public static function bootPropellerPropertyTrait()
    {
        $flushCache = function( $article ) {
            $article->flushCache();
        };

        // If the property doesn't use SoftDeletes.
        if (method_exists(static::class, 'restored')) {
            static::restored($flushCache);
        }

        static::deleted($flushCache);
        static::saved($flushCache);


        static::deleting(function ($article) {
            if (method_exists($article, 'bootSoftDeletes') && !$article->forceDeleting) {
                return;
            }
            $article->categories()->sync([]);
        });
    }

    // scopes

    /**
     * Scope where categories are
     *
     * @param $query
     * @param array $categories
     * @return mixed
     */
    public function scopeWhereCategoriesAre($query, $categories = [])
    {
        return $query->whereHas('categories', function($categoryQuery) use ( $categories ) {
            $categoryQuery->whereIn('id', $categories);
        });
    }

    /**
     * Where category is
     *
     * @param $query
     * @param $category
     * @return mixed
     */
    public function scopeWhereCategoryIs($query, $category)
    {
        return $query->whereHas('categories', function($categoryQuery) use ( $category ) {
            $categoryQuery->where('id', $category);
        });
    }


    // cacheable

    /**
     * Get the categories
     *
     * @return mixed
     */
    public function getCategories()
    {
        return $this->getCachedRelation('categories');
    }

    /**
     * Attach a single category
     *
     * @param $category
     * @return $this
     */
    public function attachCategory($category)
    {
        return $this->attachModel('categories', $category);
    }

    /**
     * Detach a single category
     *
     * @param $category
     * @return static
     */
    public function detachCategory($category)
    {
        return $this->detachModel('categories', $category);
    }

    /**
     * Attach multi categories
     *
     * @param array $categories
     * @return $this
     */
    public function attachCategories( $categories = [])
    {
        foreach ( $categories as $category )
        {
            $this->attachCategory($category);
        }
        return $this;
    }

    /**
     * Detach the categories
     *
     * @param array $categories
     * @return $this
     */
    public function detachCategories( $categories = [])
    {
        if (empty( $categories ) ) {
            $categories = $this->categories()->get();
        }

        foreach ( $categories as $category )
        {
            $this->detachCategory($category);
        }
        return $this;
    }

    /**
     * Sync the categories
     *
     * @param $categories
     * @param bool $detaching
     * @return $this
     */
    public function syncCategories($categories, $detaching = true)
    {
        return $this->syncModels('categories', $categories, $detaching);
    }

    /**
     * Sync without detaching
     *
     * @param $categories
     * @return mixed
     */
    public function syncCategoriesWithoutDetaching($categories)
    {
        return $this->syncCategories($categories, false);
    }


    /**
     * Associate user
     *
     * @param $object
     * @return static
     */
    public function associateUser($object)
    {
        return $this->associateModel('users', $object, true);
    }

    /**
     * Dissociate user
     *
     * @return static
     */
    public function dissociateUser()
    {
        return $this->associateModel('users', true);
    }


    /**
     * Categories relationship polymorphic
     *
     * @return mixed
     */
    public function categories()
    {
        return $this->morphToMany(
            Config::get('larablog.models.category'), // model
            'node', // node
            Config::get('larablog.tables.category_article'), // table
            'node_id',
            Config::get('larablog.foreign_keys.category')
        );
    }

    /**
     * user relation
     *
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo(
            Config::get('larablog.models.user'),
            Config::get('larablog.foreign_keys.user'));
    }






    /**
     * Flush cache
     *
     * See what we are caching
     */
    public function flushCache()
    {
        Cache::forget('larablog_categories_for_article_' . $this->getKey() );
    }



    /**
     * Alias to eloquent model->get() function with cached relation
     *
     * @param $relationship
     * @return mixed
     */
    private function getCachedRelation($relationship)
    {
        if ( !LarablogHelper::isValidRelationship($relationship) )
        {
            throw new InvalidArgumentException;
        }

        $cacheKey = "larablog_{$relationship}_for_article_" . $this->getKey();

        if ( !Config::get('larablog.use_cache') ) {
            return $this->$relationship()->get();
        }

        return Cache::remember($cacheKey, Config::get('cache.ttl', 60), function() use( $relationship ) {
            return $this->$relationship()->get()->toArray();
        });
    }

    /**
     * Alias to eloquent associate() method
     *
     * @param string $relationship
     * @param mixed $object
     * @return static
     */
    private function associateModel($relationship, $object, $singular = false)
    {

        if ( !LarablogHelper::isValidRelationship($relationship) )
        {
            throw new InvalidArgumentException;
        }

        $objectType = Str::singular($relationship);
        $object = LarablogHelper::getIdFor($object, $objectType);

        if ( $singular )
        {
            // In associate there is just a singular method
            $relationship = Str::singular($relationship);
        }

        $this->$relationship()->associate($object);

        $this->flushCache();

        return $this;
    }

    /**
     * Alias to eloquent dissociate method
     *
     * @param $relationship
     * @param bool $singular
     * @return $this
     */
    private function dissociateModel($relationship, $singular = false)
    {
        if ( !LarablogHelper::isValidRelationship($relationship) )
        {
            throw new InvalidArgumentException;
        }

        if ( $singular )
        {
            // In associate there is just a singular method
            $relationship = Str::singular($relationship);
        }

        $this->$relationship()->dissociate();

        $this->flushCache();
        return $this;
    }


    /**
     * Alias to eloquent attach() method
     *
     * @param $relationship
     * @param $object
     * @return $this
     */
    private function attachModel($relationship, $object)
    {
        if ( !LarablogHelper::isValidRelationship($relationship) )
        {
            throw new InvalidArgumentException;
        }

        $attributes = [];
        $objectType = Str::singular($relationship);
        $object = LarablogHelper::getIdFor($object, $objectType);

        $this->$relationship()->attach(
            $object,
            $attributes
        );

        $this->flushCache();
        $this->fireLarablogEvent("{$objectType}.attached", [$this, $object]);
        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method
     *
     * @param string $relationship
     * @param mixed $object
     * @return static
     */
    private function detachModel($relationship, $object)
    {
        if ( !LarablogHelper::isValidRelationship($relationship) )
        {
            throw new InvalidArgumentException;
        }

        $objectType = Str::singular($relationship);
        $relationshipQuery = $this->$relationship();

        $object = LarablogHelper::getIdFor($object, $objectType);

        $relationshipQuery->detach($object);

        $this->flushCache();
        $this->fireLarablogEvent("{$objectType}.detached", [$this, $object]);

        return $this;
    }

    /**
     * Alias to eloquent sync() method
     *
     * @param $relationship
     * @param $objects
     * @param bool $detaching
     * @return $this
     */
    public function syncModels($relationship, $objects, $detaching = true)
    {
        if ( !LarablogHelper::isValidRelationship($relationship) )
        {
            throw new InvalidArgumentException;
        }

        $objectType = Str::singular($relationship);
        $mappedObjects = [];

        foreach ( $objects as $object )
        {
            $mappedObjects[] = LarablogHelper::getIdFor($object, $objectType);
        }

        $relationshipToSync = $this->$relationship();

        $result = $relationshipToSync->sync($mappedObjects, $detaching);

        $this->flushCache();
        $this->fireLarablogEvent("{$objectType}.synced", [$this, $result]);

        return $this;
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