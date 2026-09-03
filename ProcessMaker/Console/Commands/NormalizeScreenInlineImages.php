<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Models\Screen;
use ProcessMaker\Screens\ScreenInlineImageNormalizer;

class NormalizeScreenInlineImages extends Command
{
    protected $signature = 'processmaker:normalize-screen-inline-images
                            {--screen= : Limit to a single screen id}
                            {--dry-run : Detect inline images without writing changes}';

    protected $description = 'Extract base64 inline images from screen config into Spatie media';

    public function handle(ScreenInlineImageNormalizer $normalizer): int
    {
        $query = Screen::query()->orderBy('id');
        if ($this->option('screen')) {
            $query->where('id', (int) $this->option('screen'));
        }

        $dryRun = (bool) $this->option('dry-run');
        $scanned = 0;
        $modified = 0;
        $converted = 0;

        $query->chunkById(50, function ($screens) use ($normalizer, $dryRun, &$scanned, &$modified, &$converted) {
            foreach ($screens as $screen) {
                $scanned++;
                $config = $screen->config;
                if (!is_array($config) || !$normalizer->configContainsInlineImages($config)) {
                    continue;
                }

                if ($dryRun) {
                    $modified++;
                    $this->line("[dry-run] Screen #{$screen->id} ({$screen->title}) contains inline images");
                    continue;
                }

                $result = $normalizer->normalize($screen, $config);
                if (!$result->wasModified()) {
                    continue;
                }

                $screen->config = $result->config();
                $screen->saveOrFail();
                $modified++;
                $converted += $result->convertedCount();
                $this->info("Screen #{$screen->id}: converted {$result->convertedCount()} image(s)");
            }
        });

        $this->info("Scanned {$scanned} screen(s); modified {$modified}; new media {$converted}");

        return self::SUCCESS;
    }
}
