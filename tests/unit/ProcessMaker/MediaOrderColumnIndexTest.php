<?php

namespace ProcessMaker;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MediaOrderColumnIndexTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_order_column_index_on_media_table(): void
    {
        // Run the migration to add the index
        $this->artisan('migrate');

        // Check if the index exists
        $indexExists = Schema::getIndexListing('media');

        $this->assertContains('media_order_column_index', $indexExists);
    }
}
