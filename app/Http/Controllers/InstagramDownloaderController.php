<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class InstagramDownloaderController extends Controller
{
    private $apiKey;
    private $apiHost;
    
    public function __construct()
    {
        $this->apiKey = env('INSTAGRAM_API_KEY', '36f0e0b932msh6d41dd073a47598p15fa16jsn4d484688794a');
        $this->apiHost = env('INSTAGRAM_API_HOST', 'instagram-downloader-scraper-reels-igtv-posts-stories.p.rapidapi.com');
    }

    /**
     * Get Instagram media information
     */
    public function getMedia(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url|regex:/instagram\.com/'
        ]);

        try {
            $response = Http::withHeaders([
                'x-rapidapi-key' => $this->apiKey,
                'x-rapidapi-host' => $this->apiHost
            ])->get('https://instagram-downloader-download-instagram-stories-videos4.p.rapidapi.com/convert', [
                'url' => $request->url
            ]);
            

            if ($response->successful()) {
                $data = $response->json();
                
                // Process the media data
                if (isset($data['media']) && count($data['media']) > 0) {
                    $media = $data['media'][0];
                    
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'type' => $media['type'] ?? 'unknown',
                            'quality' => $media['quality'] ?? 'HD',
                            'thumbnail_proxy' => route('instagram.proxy.thumbnail', [
                                'url' => base64_encode($media['thumbnail'] ?? '')
                            ]),
                            'download_url' => route('instagram.download', [
                                'url' => base64_encode($media['url'] ?? ''),
                                'type' => $media['type'] ?? 'video',
                                'filename' => 'instagram_' . time()
                            ])
                        ]
                    ]);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'No media found'
                ], 404);
                
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch media information'
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proxy thumbnail images to avoid CORS issues
     */
    public function proxyThumbnail(Request $request)
    {
        $url = base64_decode($request->url);
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            abort(404);
        }

        try {
            $response = Http::timeout(10)->get($url);
            
            if ($response->successful()) {
                $contentType = $response->header('Content-Type') ?: 'image/jpeg';
                
                return response($response->body())
                    ->header('Content-Type', $contentType)
                    ->header('Cache-Control', 'public, max-age=3600')
                    ->header('Access-Control-Allow-Origin', '*');
            }
            
            abort(404);
            
        } catch (\Exception $e) {
            abort(404);
        }
    }

    /**
     * Download media file
     */
    public function downloadMedia(Request $request)
    {
        $url = base64_decode($request->url);
        $type = $request->type ?? 'video';
        $filename = $request->filename ?? 'instagram_download';
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            abort(404);
        }

        try {
            $response = Http::timeout(30)->get($url);
            
            if ($response->successful()) {
                $extension = $type === 'video' ? '.mp4' : '.jpg';
                $downloadName = $filename . $extension;
                
                return response($response->body())
                    ->header('Content-Type', $type === 'video' ? 'video/mp4' : 'image/jpeg')
                    ->header('Content-Disposition', 'attachment; filename="' . $downloadName . '"')
                    ->header('Cache-Control', 'no-cache, must-revalidate');
            }
            
            abort(404);
            
        } catch (\Exception $e) {
            abort(404);
        }
    }

    /**
     * Get download statistics (optional)
     */
    public function getStats(): JsonResponse
    {
        // You can store actual stats in database
        // For now returning static data
        return response()->json([
            'downloads' => 5231897 + rand(1, 100) // Simulated increment
        ]);
    }
}