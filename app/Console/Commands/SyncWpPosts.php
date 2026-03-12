<?php

namespace App\Console\Commands;

use App\Models\WpSite;
use App\Services\WordPressService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncWpPosts extends Command
{
    protected $signature = 'app:sync-wp-posts';
    protected $description = 'Sync posts from all active WordPress sites';
    protected $wordPressService;

    public function __construct(WordPressService $wordPressService)
    {
        parent::__construct();
        $this->wordPressService = $wordPressService;
    }

    public function handle()
    {
        $sites = WpSite::where('is_connected', true)->get();
        $this->info("Starting sync for {$sites->count()} active WordPress sites");

        foreach ($sites as $site) {
            try {
                $this->info("Syncing posts for site: {$site->site_name} ({$site->site_url})");
                
                // Sync posts
                $result = $this->wordPressService->syncPosts($site);
                
                // Update last sync time
                $site->update(['last_synced_at' => now()]);
                
                $this->info("Successfully synced {$result['synced_count']} posts for {$site->site_name}");
                
                if (!empty($result['errors'])) {
                    $this->warn("Encountered " . count($result['errors']) . " errors while syncing {$site->site_name}");
                    foreach ($result['errors'] as $error) {
                        $this->error($error);
                    }
                }
                
            } catch (\Exception $e) {
                Log::error("Failed to sync posts for site {$site->id}", [
                    'site_id' => $site->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $this->error("Error syncing {$site->site_name}: " . $e->getMessage());
            }
        }

        $this->info('Sync process completed');
        return Command::SUCCESS;
    }
}
