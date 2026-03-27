<?php

namespace App\Console\Commands;

use App\Models\WpSite;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestWordPressCron extends Command
{
    protected $signature = 'test:wp-cron';
    protected $description = 'Test WordPress cron for all sites';

    public function handle()
    {
        $this->info('Testing WordPress cron for all sites...');
        
        $sites = WpSite::all();
        
        foreach ($sites as $site) {
            $this->line("Testing cron for: {$site->site_name}");
            
            try {
                $cronUrl = rtrim($site->site_url, '/') . '/wp-cron.php?doing_wp_cron';
                
                $response = file_get_contents($cronUrl, false, stream_context_create([
                    'http' => [
                        'timeout' => 10,
                        'method' => 'GET'
                    ]
                ]));
                
                $this->info("✅ SUCCESS: Cron triggered for {$site->site_name}");
                
                Log::info('WordPress cron triggered', [
                    'site_id' => $site->id,
                    'site_name' => $site->site_name,
                    'cron_url' => $cronUrl,
                    'response' => $response ? 'success' : 'failed'
                ]);
                
            } catch (\Exception $e) {
                $this->error("❌ ERROR: {$e->getMessage()}");
                
                Log::error('Failed to trigger WordPress cron', [
                    'site_id' => $site->id,
                    'site_name' => $site->site_name,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->info('WordPress cron test completed!');
        return 0;
    }
}