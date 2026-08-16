<?php

namespace App\Console\Commands;

use App\Models\StyleSampling;
use App\Services\AI\StyleEmbeddingIndexer;
use Illuminate\Console\Command;

class IndexStyleEmbeddings extends Command
{
    protected $signature = 'styles:semantic-index {--force : Rebuild embeddings even when the source is unchanged}';

    protected $description = 'Build semantic search embeddings for eligible Style Sampling records';

    public function handle(StyleEmbeddingIndexer $indexer): int
    {
        if (! config('services.ai_search.enabled')) {
            $this->error('AI Smart Search is disabled. Set AI_SEARCH_ENABLED=true first.');

            return self::FAILURE;
        }

        $counts = ['Indexed' => 0, 'Skipped' => 0, 'Failed' => 0];
        $styles = StyleSampling::published()
            ->whereNotNull('style_file_path')
            ->whereIn('category', StyleSampling::CUSTOMER_STYLE_CATEGORIES)
            ->orderBy('id')
            ->get();

        $bar = $this->output->createProgressBar($styles->count());

        foreach ($styles as $style) {
            try {
                $indexed = $indexer->index($style, (bool) $this->option('force'));
                $counts[$indexed ? 'Indexed' : 'Skipped']++;
            } catch (\Throwable $exception) {
                report($exception);
                $counts['Failed']++;
            } finally {
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->table(['Indexed', 'Skipped', 'Failed'], [[...array_values($counts)]]);

        return $counts['Failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
