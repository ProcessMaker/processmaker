<?php

namespace ProcessMaker;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MediaOrderColumnIndexTest extends TestCase
{
    /** @test */
    public function test_it_has_order_column_index_on_media_table()
    {
        // Run the migration to add the index
        $this->artisan('migrate');

        // Check if the index exists
        $indexExists = Schema::getIndexListing('media');

        $this->assertContains('media_order_column_index', $indexExists);
    }
}
