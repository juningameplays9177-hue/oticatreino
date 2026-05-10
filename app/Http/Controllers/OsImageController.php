<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Models\ServiceOrderImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OsImageController extends Controller
{
    public function store(Request $request, ServiceOrder $o)
    {
        $request->validate([
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $existingImages = $o->images()->count();
        if ($existingImages >= 5) {
            return response()->json(['error' => 'Limite de 5 imagens atingido.'], 400);
        }

        $images = $request->file('images');
        $uploaded = [];

        foreach ($images as $image) {
            if ($existingImages >= 5) {
                break;
            }

            $existingPositions = $o->images()->pluck('position')->toArray();
            $availablePositions = array_diff([1, 2, 3, 4, 5], $existingPositions);
            $position = !empty($availablePositions) ? min($availablePositions) : $existingImages + 1;

            $path = $image->store("os/{$o->id}", 'public');

            $osImage = $o->images()->create([
                'path' => $path,
                'position' => $position,
            ]);

            $uploaded[] = [
                'id' => $osImage->id,
                'path' => Storage::url($path),
                'position' => $position,
            ];

            $existingImages++;
        }

        return response()->json(['success' => true, 'images' => $uploaded]);
    }

    public function destroy(ServiceOrderImage $osImage)
    {
        $serviceOrder = $osImage->serviceOrder;

        if (Storage::disk('public')->exists($osImage->path)) {
            Storage::disk('public')->delete($osImage->path);
        }

        $osImage->delete();

        return response()->json(['success' => true]);
    }
}

