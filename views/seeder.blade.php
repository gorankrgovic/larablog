<?php echo '<?php' ?>

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LarablogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Default category
        $this->command->info('Creating Default category');

        $do = \{{ $category }}::create([
            'name' => '{{ config('larablog.default_category.name') }}',
            'slug' => '{{ config('larablog.default_category.slug') }}'
        ]);
    }

}