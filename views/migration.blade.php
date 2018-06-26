<?php echo '<?php' ?>

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class LarablogSetupTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        // Create table for the property identifications
        Schema::create('{{ $larablog['tables']['categories'] }}', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Create table for storing pets
        Schema::create('{{ $larablog['tables']['articles'] }}', function(Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('{{ $larablog['foreign_keys']['user'] }}')->nullable()->index();
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['{!! implode("','", $larablog['article_statuses']) !!}'])->index();
            $table->timestamp('publish_at')->nullable();
            $table->timestamps();

        });


        // INTERMEDIATE TABLES MIGRATION
        Schema::create('{{ $larablog['tables']['category_article'] }}', function(Blueprint $table) {
            $table->unsignedInteger('{{ $larablog['foreign_keys']['category'] }}');
            $table->unsignedInteger('node_id')->index();
            $table->string('node_type');

            $table->foreign('{{ $larablog['foreign_keys']['category'] }}')->references('id')->on('{{ $larablog['tables']['categories'] }}')->onUpdate('cascade')->onDelete('cascade');
            $table->primary(['{{ $larablog['foreign_keys']['category'] }}', 'node_id', 'node_type']);
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('{{ $larablog['tables']['categories'] }}');
        Schema::dropIfExists('{{ $larablog['tables']['articles'] }}');
        Schema::dropIfExists('{{ $larablog['tables']['category_article'] }}');
    }
}