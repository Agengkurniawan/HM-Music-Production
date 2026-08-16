<?php

namespace App\Console\Commands;

use App\Models\StyleSampling;
use Database\Seeders\AiSearchDangdutTestSeeder;
use Illuminate\Console\Command;

class ClearAiSearchTestCatalog extends Command
{
    protected $signature = 'styles:test-catalog-clear {--force : Skip environment confirmation}';

    protected $description = 'Delete only local AI test catalog rows marked with [AI-TEST-2026]';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])
            && ! $this->option('force')
            && ! $this->confirm('This is not a local/testing environment. Delete marked AI test rows?')) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $deleted = StyleSampling::query()
            ->where('ai_enrichment_source', 'test_seed')
            ->where('description', 'like', '%'.AiSearchDangdutTestSeeder::MARKER.'%')
            ->delete();

        $this->info("Deleted: {$deleted}");

        return self::SUCCESS;
    }
}
