<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    /**
     * Get list of active templates
     */
    public function index(Request $request): JsonResponse
    {
        $query = Template::with(['category', 'latestVersion'])
            ->where('is_active', true);

        if ($request->has('category_slug')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category_slug);
            });
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $templates = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * Get template details with schema
     */
    public function show(int $id): JsonResponse
    {
        $template = Template::with(['category', 'latestVersion'])
            ->where('is_active', true)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $template,
        ]);
    }

    /**
     * Get list of template categories
     */
    public function categories(): JsonResponse
    {
        $categories = TemplateCategory::where('is_active', true)
            ->withCount('templates')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }
}
