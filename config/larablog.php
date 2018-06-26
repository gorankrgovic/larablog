<?php

return [

    /*
   |--------------------------------------------------------------------------
   | Use cache
   |--------------------------------------------------------------------------
   |
   | Use cache for certain relations in the Larablog
   |
   */
    'use_cache' => true,

    /*
    |--------------------------------------------------------------------------
    | Larablog User Models
    |--------------------------------------------------------------------------
    |
    | This is the array that contains the information of the user models.
    | This information is used in the add-trait command
    |
    */
    'user_models' => [
        'users' => 'App\User',
    ],


    /*
   |--------------------------------------------------------------------------
   | Default category name
   |--------------------------------------------------------------------------
   |
   | Larablog require a default category to be added
   |
   */
    'default_category' => [
        'name' => 'Uncategorized',
        'slug' => 'uncategorized'
    ],


    /*
   |--------------------------------------------------------------------------
   | Default excerpt character length
   |--------------------------------------------------------------------------
   |
   | Excerpt character length
   */
    'excerpt_length' => 250,

    /*
    |--------------------------------------------------------------------------
    | Article statuses
    |--------------------------------------------------------------------------
    |
    | The array of the article statuses
    |
    */
    'article_statuses' => [
        // Viewable by everyone
        'publish',
        // Scheduled to be published in a future date
        'future',
        // Incomplete article viewable by anyone with proper role or custom
        'draft',
        // Paused listing
        'paused',
        // Awaiting the (owner) to publish if you have the agents or whatever
        'pending',
        // System admin has blocked for uncertain reason (reason can be set in a field)
        'blocked',
        // Revision that system saves while the authors is editing for example (optional)
        'auto-draft',
    ],


    /*
   |--------------------------------------------------------------------------
   | Larablog Models
   |--------------------------------------------------------------------------
   |
   | These are the models used by Larablog to define the tables
   | If you want the Larablog models to be in a different namespace or
   | to have a different name, you can do it here.
   |
   */
    'models' => [
        /**
         * User model
         */
        'user' => 'App\User',
        /**
         * Category model
         */
        'category' => 'App\Larablog\Category',
        /**
         * Article model
         */
        'article' => 'App\Larablog\Article'
    ],


    /*
    |--------------------------------------------------------------------------
    | Larablog Tables
    |--------------------------------------------------------------------------
    |
    | These are the tables used by Larablog to store all the necessary data.
    |
    */
    'tables' => [
        /**
         * If you have a different table for users..
         */
        'users' => 'users',
        /**
         * Categories table.
         */
        'categories' => 'categories',
        /**
         * Articles table
         */
        'articles' => 'articles',

        // INTERMEDIATE TABLES
        /**
         * ArticleCategory intermediate table (Polymorphic relation)
         */
        'category_article' => 'category_article'
    ],

    /*
   |--------------------------------------------------------------------------
   | Larablog Foreign Keys
   |--------------------------------------------------------------------------
   |
   | These are the foreign keys used by larablog in the intermediate tables.
   |
   */
    'foreign_keys' => [
        /**
         * User foreign key
         */
        'user' => 'user_id',
        /**
         * Property foreign key
         */
        'category' => 'category_id',
        /**
         * Pet foreign key
         */
        'article' => 'article_id'
    ],



];