<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show($businessId)
    {
        $business = Business::findOrFail($businessId);
        
        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $page = $business->page()->with('blocks')->firstOrFail();
        return response()->json($page);
    }

    public function updateBlocks(Request $request, $businessId)
    {
        $business = Business::findOrFail($businessId);
        
        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'blocks' => 'required|array',
            'blocks.*.id' => 'sometimes|exists:page_blocks,id',
            'blocks.*.type' => 'required|string|in:hero,services,about,contact',
            'blocks.*.content' => 'required|array',
            'blocks.*.order' => 'required|integer|min:0',
        ]);

        $page = $business->page()->firstOrFail();

        foreach ($request->blocks as $blockData) {
            if (isset($blockData['id'])) {
                // Actualizar bloque existente
                $page->blocks()->where('id', $blockData['id'])->update([
                    'type' => $blockData['type'],
                    'content' => $blockData['content'],
                    'order' => $blockData['order'],
                ]);
            } else {
                // Crear nuevo bloque
                $page->blocks()->create([
                    'type' => $blockData['type'],
                    'content' => $blockData['content'],
                    'order' => $blockData['order'],
                ]);
            }
        }

        return response()->json($page->load('blocks'));
    }

    public function deleteBlock($businessId, $blockId)
    {
        $business = Business::findOrFail($businessId);
        
        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $page = $business->page()->firstOrFail();
        $page->blocks()->where('id', $blockId)->delete();

        return response()->json(['message' => 'Bloque eliminado'], 200);
    }
}