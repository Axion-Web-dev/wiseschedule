<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TwitterController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/test-encryption', function () {
    try {
        $secret = "Hello World";
        $encrypted = encrypt($secret);
        return decrypt($encrypted); // This should return "Hello World"
    } catch (\Exception $e) {
        return "Encryption is still broken: " . $e->getMessage();
    }
});

// Test Hugging Face Image Generation
Route::get('/test-hf-gen', function () {
    try {
        $prompt = "A futuristic glass building in a forest, highly detailed, 8k";
        
        $response = Http::withToken(env('HF_TOKEN'))
            ->post('https://router.huggingface.co/models/black-forest-labs/FLUX.1-schnell', [
                'inputs' => $prompt,
            ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Model is still loading or limit hit'], 503);
        }

        // The response is the raw image data (binary)
        $imageBinary = $response->body();
        
        // Save it to your local storage
        $filename = 'generated_' . time() . '.jpg';
        Storage::disk('public')->put($filename, $imageBinary);

        return "✅ Image saved as: " . asset('storage/' . $filename);
    } catch (\Exception $e) {
        return "❌ Error: " . $e->getMessage();
    }
});

// Twitter OAuth Routes with web middleware
Route::middleware(['web'])->group(function () {
    Route::get('/x/redirect', [TwitterController::class, 'redirect'])->name('twitter.redirect');
    Route::get('/x/callback', [TwitterController::class, 'callback'])->name('twitter.callback');
    Route::get('/x/disconnect', [TwitterController::class, 'disconnect'])->name('twitter.disconnect');
});
