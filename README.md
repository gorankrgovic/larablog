# Larablog

A package for easier maintenance of the blog (or news) articles.


## Installation, Configuration and Usage

Via Composer:

```bash
$ composer require gorankrgovic/larablog
```
### Configuration

Once you install the package, it should be automatically discovered by the Laravel. To check this, in your terminal simply run the:

```bash
$ php artisan
```

Once you run the command, you shoul dbe able to find all `larablog` commands available.

First step is to publish the vendor files. In this case only the config file.

```bash
$ php artisan vendor:publish --tag="larablog"
```

After you inspect the configuration file, it depends on your skills :). I recommend running the:

```bash
$ php artisan larablog:setup
```

The above command will install models, make the migration file and add a trait to your User model.

After the setup please run the migration command

```bash
$ php artisan migrate
```

Which will create the tables for this package: articles and categories. (you can change the names in your config file).

Once the tables are ready, please make sure to seed the initial category.

```bash
$ php artisan larablog:seeder
```

The above command will create a seeder class. After you created a seeder, please run the

```bash
$ composer du
```

And then run the seeder

```bash
$ php artisan db:seed --class=LarablogSeeder
```

Thats. basically it.

### Usage

Create your controller resource or whatever and then simply:

```php
$article = new Article([
            'title' => 'This is my title',
            'slug' => 'this-is-my-slug',
            'excerpt' => 'This is my excerpt',
            'content' => 'This is my content',
            'is_featured' => false,
            'status' => 'publish',
            'publish_at' => Carbon::now()
]);

// associate the user
$article->associateUser(1);
// save the article
$article->save();

// attach the category
$article->attachCategory('uncategorized'); // name or the ID

// or multiple categories
$article->attachCategories([1, 2, 3]); // names or ID's
```

To get the article you can, for example:

```php 
$article = Article::find(1);

// get the categories (cacheable)
$article->getCategories();
```

Or you can fetch all articles from a user

```php
$user = User::find(1);

$articles = $user->getArticles();

// or
$articles = $user->getArticlesPaginated(20);

// or
$articles = $user->articles()->get();
```

Also you can use provided scopes

```php 

$articles = Article::whereCategoriesAre([1, 2, 3])->get(); // pass an array of IDs

// or

$articles = Article::whereCategoryIs(1)->get(); // pass an id
```

## Facade

Larablog provides you with convenient helpers to make your life easier

```php
use Gorankrgovic\Larablog\Facades\Larablog;

// ... and then in your methods...

// to generate a unique slug which will append number at the end of the slug if the same slug exists
$slug = Larablog::slug($title);

// to convert string to paragraph delimited text
$text = Larablog::autop($content);

// to generate the excerpt
$excerpt = Larablog::excerpt($content);

// to just sanitize the title and to get a slug
$slug = Larablog::sanitize_title($title);

```

Basically thats it...