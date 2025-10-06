<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show($businessId)
    {
        $business = Business::findOrFail($businessId);

        if (auth()->check() && $business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $page = $business->page;

        if (!$page) {
            return response()->json(['message' => 'Página no encontrada'], 404);
        }

        return response()->json($page);
    }

    public function update(Request $request, $businessId)
    {
        $business = Business::findOrFail($businessId);

        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'content' => 'sometimes|required|array',
            'theme_config' => 'sometimes|array',
            'is_published' => 'sometimes|boolean'
        ]);

        $page = $business->page;

        if (!$page) {
            $page = $business->page()->create([
                'content' => $request->input('content', ['sections' => []]),
                'theme_config' => $request->input('theme_config', []),
                'is_published' => $request->input('is_published', false)
            ]);
        } else {
            $page->update($request->only(['content', 'theme_config', 'is_published']));
        }

        return response()->json($page);
    }

    // Método público para ver página publicada
    public function publicShow($slug)
    {
        $business = Business::where('slug', $slug)
            ->where('is_active', true)
            ->with(['page', 'services' => function($query) {
                $query->where('is_active', true);
            }])
            ->firstOrFail();

        if (!$business->page || !$business->page->is_published) {
            return response()->json(['message' => 'Página no publicada'], 404);
        }

        return response()->json([
            'page' => $business->page,
            'business' => $business->only(['business_name', 'description', 'logo_url', 'phone', 'email', 'address']),
            'services' => $business->services
        ]);
    }
}