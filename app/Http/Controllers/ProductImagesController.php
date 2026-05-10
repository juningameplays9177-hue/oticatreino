<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImagesController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $existingImages = $product->images()->count();
        if ($existingImages >= 5) {
            return response()->json(['error' => 'Limite de 5 imagens atingido.'], 400);
        }

        $images = $request->file('images');
        $uploaded = [];

        foreach ($images as $image) {
            if ($existingImages >= 5) {
                break;
            }

            // Encontrar próxima posição disponível
            $existingPositions = $product->images()->pluck('position')->toArray();
            $availablePositions = array_diff([1, 2, 3, 4, 5], $existingPositions);
            $position = !empty($availablePositions) ? min($availablePositions) : $existingImages + 1;

            // Salvar arquivo
            $path = $image->store("products/{$product->id}", 'public');

            // Criar registro
            $productImage = $product->images()->create([
                'path' => $path,
                'position' => $position,
            ]);

            $uploaded[] = [
                'id' => $productImage->id,
                'path' => Storage::url($path),
                'position' => $position,
            ];

            $existingImages++;
        }

        return response()->json([
            'success' => true,
            'images' => $uploaded,
        ]);
    }

    public function destroy(ProductImage $productImage)
    {
        $product = $productImage->product;

        // Deletar arquivo
        if (Storage::disk('public')->exists($productImage->path)) {
            Storage::disk('public')->delete($productImage->path);
        }

        // Deletar registro
        $productImage->delete();

        return response()->json(['success' => true]);
    }
}

