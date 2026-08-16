<?php

namespace App\Console\Commands;

use App\Models\StyleSampling;
use App\Services\AI\CatalogEnrichmentService;
use Illuminate\Console\Command;

class EnrichStyleCatalog extends Command
{
    protected $signature = 'styles:ai-enrich {--force : Refresh metadata even when the source is unchanged} {--limit= : Maximum styles to process}';

    protected $description = 'Enrich eligible Style Sampling records with internal AI search metadata';

    public function handle(CatalogEnrichmentService $enrichment): int
    {
        if (! config('services.ai_enrichment.enabled')) {
            $this->error('AI catalog enrichment is disabled. Set AI_ENRICHMENT_ENABLED=true first.');

            return self::FAILURE;
        }

        $query = StyleSampling::published()
            ->whereNotNull('style_file_path')
            ->whereIn('category', StyleSampling::CUSTOMER_STYLE_CATEGORIES)
            ->orderBy('id');

        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }

        $styles = $query->get();
        $counts = ['Enriched' => 0, 'Skipped' => 0, 'Unrecognized' => 0, 'Failed' => 0];
        $bar = $this->output->createProgressBar($styles->count());

        foreach ($styles as $style) {
            try {
                $result = $enrichment->enrich($style, (bool) $this->option('force'));
                $counts[ucfirst($result)]++;
            } catch (\Throwable $exception) {
                report($exception);
                $counts['Failed']++;
            } finally {
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->table(array_keys($counts), [[...array_values($counts)]]);

        return $counts['Failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
