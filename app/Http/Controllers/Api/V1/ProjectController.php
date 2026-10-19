<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\SharedLink;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    /**
     * Get list of user's projects
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->projects()
            ->with(['template', 'media', 'sharedLink']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $projects = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }

    /**
     * Create new draft project
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:templates,id',
            'title' => 'required|string|max:255',
            'recipient_name' => 'required|string|max:255',
            'age' => 'nullable|integer|min:1|max:150',
            'birthday_date' => 'nullable|date',
            'sender_name' => 'nullable|string|max:255',
            'short_message' => 'nullable|string',
            'letter_content' => 'nullable|string',
            'closing_message' => 'nullable|string',
            'custom_settings' => 'nullable|array',
        ]);

        $template = Template::with('latestVersion')->findOrFail($validated['template_id']);

        // Generate unique slug
        $baseSlug = Str::slug($validated['recipient_name'] . '-birthday');
        $slug = $baseSlug . '-' . Str::lower(Str::random(6));

        while (Project::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . Str::lower(Str::random(6));
        }

        $project = $request->user()->projects()->create([
            'template_id' => $template->id,
            'template_version_id' => $template->latestVersion->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'status' => 'DRAFT',
            'recipient_name' => $validated['recipient_name'],
            'age' => $validated['age'] ?? 20,
            'birthday_date' => $validated['birthday_date'] ?? null,
            'sender_name' => $validated['sender_name'] ?? null,
            'short_message' => $validated['short_message'] ?? null,
            'letter_content' => $validated['letter_content'] ?? null,
            'closing_message' => $validated['closing_message'] ?? null,
            'custom_settings' => $validated['custom_settings'] ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Draft project created successfully.',
            'data' => $project->load(['template', 'templateVersion', 'media']),
        ], 201);
    }

    /**
     * Get project details
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $project = $request->user()->projects()
            ->with(['template', 'templateVersion', 'media', 'sharedLink'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $project,
        ]);
    }

    /**
     * Update project data
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $project = $request->user()->projects()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'recipient_name' => 'sometimes|string|max:255',
            'age' => 'nullable|integer|min:1|max:150',
            'birthday_date' => 'nullable|date',
            'sender_name' => 'nullable|string|max:255',
            'short_message' => 'nullable|string',
            'letter_content' => 'nullable|string',
            'closing_message' => 'nullable|string',
            'custom_settings' => 'nullable|array',
        ]);

        $project->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully.',
            'data' => $project->fresh(['template', 'templateVersion', 'media', 'sharedLink']),
        ]);
    }

    /**
     * Delete project
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $project = $request->user()->projects()->findOrFail($id);
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully.',
        ]);
    }

    /**
     * Publish project and generate public share link
     */
    public function publish(int $id, Request $request): JsonResponse
    {
        $project = $request->user()->projects()
            ->with(['media', 'templateVersion'])
            ->findOrFail($id);

        // Validation for minimal requirements
        if (empty($project->recipient_name)) {
            return response()->json([
                'success' => false,
                'message' => 'Recipient name is required to publish greeting.',
            ], 422);
        }

        $project->update([
            'status' => 'PUBLISHED',
            'published_at' => now(),
        ]);

        // Create or update SharedLink
        $sharedLink = SharedLink::firstOrCreate(
            ['project_id' => $project->id],
            [
                'slug' => $project->slug,
                'is_active' => true,
            ]
        );

        $publicUrl = url('/g/' . $sharedLink->slug);

        return response()->json([
            'success' => true,
            'message' => 'Project published successfully.',
            'data' => [
                'project' => $project->fresh(['template', 'media', 'sharedLink']),
                'share_url' => $publicUrl,
                'slug' => $sharedLink->slug,
            ],
        ]);
    }

    /**
     * Unpublish project back to DRAFT
     */
    public function unpublish(int $id, Request $request): JsonResponse
    {
        $project = $request->user()->projects()->findOrFail($id);

        $project->update([
            'status' => 'DRAFT',
        ]);

        if ($project->sharedLink) {
            $project->sharedLink->update(['is_active' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Project status reverted to draft.',
            'data' => $project->fresh(['template', 'media', 'sharedLink']),
        ]);
    }
}
