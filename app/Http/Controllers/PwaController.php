<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PwaController extends Controller
{
    /**
     * Retorna o manifest.json com headers corretos
     */
    public function manifest()
    {
        $manifestPath = public_path('manifest.json');
        
        if (!file_exists($manifestPath)) {
            abort(404);
        }
        
        $manifest = json_decode(file_get_contents($manifestPath), true);
        
        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json');
    }
    
    /**
     * Retorna o service worker
     */
    public function serviceWorker()
    {
        $swPath = public_path('sw.js');
        
        if (!file_exists($swPath)) {
            abort(404);
        }
        
        $content = file_get_contents($swPath);
        
        return response($content, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Service-Worker-Allowed', '/');
    }
}

