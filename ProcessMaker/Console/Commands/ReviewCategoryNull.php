<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Screen;

class ReviewCategoryNull extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'category:review-category-assign';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the category_id column for null values.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $table = 'screens';
        $field = 'screen_category_id';
        $class = Screen::class;
        $default = DB::table('screen_categories')->where('name', 'Uncategorized')->pluck('id')->first();

        DB::table($table)->select('id', 'title')
            ->whereNull([$field, 'key'])
            ->orderBy('id', 'DESC')->chunkById(100, function (Collection $items) use ($table, $field, $class, $default) {
                foreach ($items as $item) {
                    $category = DB::table('category_assignments')
                        ->select('category_id')
                        ->where([
                            ['assignable_type', '=', $class],
                            ['assignable_id', '=', $item->id],
                        ])->first();

                    $categoryId = $default;
                    if ($category) {
                        $categoryId = $category->category_id;
                    }

                    DB::table($table)
                        ->where('id', $item->id)
                        ->update([$field => $categoryId]);

                    $this->info('- ' . $item->title . '   ==>   Category: ' . $categoryId);
                }
            });
    }
}
