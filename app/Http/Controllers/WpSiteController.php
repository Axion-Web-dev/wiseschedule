<?php

namespace App\Http\Controllers;

use App\Models\WpSite;
use App\Services\WordPressService;
use Illuminate\Http\Request;

class WpSiteController extends Controller
{
    /**
     * Test WordPress connection
     */
    public function testConnection(WpSite $record)
    {
        try {
            $wpService = new WordPressService();
            $connected = $wpService->testConnection($record);
            
            if ($connected) {
                // Update connection status and last synced time
                $record->update([
                    'is_connected' => true, 
                    'last_synced_at' => now()
                ]);
                
                return redirect()
                    ->route('filament.admin.resources.wp-sites.index')
                    ->with('success', 'Successfully connected to WordPress site!');
            } else {
                return redirect()
                    ->route('filament.admin.resources.wp-sites.index')
                    ->with('error', 'Failed to connect to WordPress site. Please check your credentials.');
            }
        } catch (\Exception $e) {
            return redirect()
                ->route('filament.admin.resources.wp-sites.index')
                ->with('error', 'Connection test failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Disconnect WordPress connection
     */
    public function disconnect(WpSite $record)
    {
        try {
            // Update connection status
            $record->update(['is_connected' => false]);
            
            return redirect()
                ->route('filament.admin.resources.wp-sites.index')
                ->with('success', 'Successfully disconnected from WordPress site.');
        } catch (\Exception $e) {
            return redirect()
                ->route('filament.admin.resources.wp-sites.index')
                ->with('error', 'Failed to disconnect: ' . $e->getMessage());
        }
    }
}
