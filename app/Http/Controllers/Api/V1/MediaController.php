<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Upload or replace media in a project slot
     */
    public function upload(int $projectId, Request $request): JsonResponse
    {
        $project = $request->user()->projects()->findOrFail($projectId);

        $validated = $request->validate([
            'slot_key' => 'required|string|max:50',
            'type' => 'required|in:photo,video,music',
            'file' => 'required|file|max:51200', // max 50MB
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $file = $request->file('file');
        $type = $validated['type'];
        $slotKey = $validated['slot_key'];

        // Validate MIME type based on media type
        if ($type === 'photo' && !in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])) {
            return response()->json(['success' => false, 'message' => 'Invalid image format. Allowed: JPG, PNG, WEBP.'], 422);
        }

        if ($type === 'video' && !in_array($file->getMimeType(), ['video/mp4', 'video/webm', 'video/quicktime'])) {
            return response()->json(['success' => false, 'message' => 'Invalid video format. Allowed: MP4, WEBM.'], 422);
        }

        if ($type === 'music' && !in_array($file->getMimeType(), ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav'])) {
            return response()->json(['success' => false, 'message' => 'Invalid audio format. Allowed: MP3, WAV.'], 422);
        }

        // Check if media already exists in this slot
        $existingMedia = ProjectMedia::where('project_id', $project->id)
            ->where('slot_key', $slotKey)
            ->first();

        if ($existingMedia && Storage::disk('public')->exists($existingMedia->file_path)) {
            Storage::disk('public')->delete($existingMedia->file_path);
        }

        // Store new file
        $fileName = $slotKey . '_' . time() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs("projects/{$project->id}/{$type}", $fileName, 'public');
        $fileUrl = asset('storage/' . $filePath);

        $media = ProjectMedia::updateOrCreate(
            [
                'project_id' => $project->id,
                'slot_key' => $slotKey,
            ],
            [
                'type' => $type,
                'file_path' => $filePath,
                'file_url' => $fileUrl,
                'original_name' => $file->getClientOriginalName(),
                'title' => $validated['title'] ?? ($existingMedia->title ?? null),
                'caption' => $validated['caption'] ?? ($existingMedia->caption ?? null),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'sort_order' => $validated['sort_order'] ?? ($existingMedia->sort_order ?? 0),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Media uploaded successfully.',
            'data' => $media,
        ]);
    }

    /**
     * Delete media from a slot
     */
    public function destroy(int $projectId, string $slotKey, Request $request): JsonResponse
    {
        $project = $request->user()->projects()->findOrFail($projectId);

        $media = ProjectMedia::where('project_id', $project->id)
            ->where('slot_key', $slotKey)
            ->firstOrFail();

        if (Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        $media->delete();

        return response()->json([
            'success' => true,
            'message' => 'Media removed successfully.',
        ]);
    }
}
