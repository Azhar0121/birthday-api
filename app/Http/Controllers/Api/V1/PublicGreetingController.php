<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\SharedLink;
use Illuminate\Http\JsonResponse;

class PublicGreetingController extends Controller
{
    /**
     * Get public greeting payload by slug (NO LOGIN REQUIRED)
     */
    public function show(string $slug): JsonResponse
    {
        // Find by shared link slug or project slug
        $sharedLink = SharedLink::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($sharedLink) {
            $project = Project::with(['template', 'templateVersion', 'media'])
                ->where('id', $sharedLink->project_id)
                ->where('status', 'PUBLISHED')
                ->firstOrFail();

            // Increment views
            $sharedLink->increment('views_count');
            $sharedLink->update(['last_viewed_at' => now()]);
            $project->increment('views_count');
        } else {
            // Direct project slug fallback
            $project = Project::with(['template', 'templateVersion', 'media'])
                ->where('slug', $slug)
                ->where('status', 'PUBLISHED')
                ->firstOrFail();

            $project->increment('views_count');
        }

        // Format media slots into structured payload for Flutter Template Engine
        $photos = [];
        $videos = [];
        $musicUrl = null;

        foreach ($project->media as $item) {
            if ($item->type === 'photo') {
                $photos[] = [
                    'slot_key' => $item->slot_key,
                    'image' => $item->file_url,
                    'title' => $item->title ?? 'Beautiful Memory',
                    'caption' => $item->caption ?? '',
                    'sort_order' => $item->sort_order,
                ];
            } elseif ($item->type === 'video') {
                $videos[] = [
                    'slot_key' => $item->slot_key,
                    'url' => $item->file_url,
                    'title' => $item->title ?? 'Moving Memory',
                ];
            } elseif ($item->type === 'music') {
                $musicUrl = $item->file_url;
            }
        }

        // Sort photos by slot / sort_order
        usort($photos, fn($a, $b) => strcmp($a['slot_key'], $b['slot_key']));
        usort($videos, fn($a, $b) => strcmp($a['slot_key'], $b['slot_key']));

        // First 4 photos for Memory Preview
        $previewPhotos = array_slice($photos, 0, 4);

        $payload = [
            'id' => $project->id,
            'title' => $project->title,
            'slug' => $project->slug,
            'recipient_name' => $project->recipient_name,
            'age' => $project->age ?? 20,
            'birthday_date' => $project->birthday_date?->format('Y-m-d'),
            'sender_name' => $project->sender_name,
            'short_message' => $project->short_message,
            'letter_content' => $project->letter_content,
            'closing_message' => $project->closing_message,
            'custom_settings' => $project->custom_settings ?? [],
            'template' => [
                'code' => $project->template->code,
                'name' => $project->template->name,
                'version' => $project->templateVersion->version,
                'schema' => $project->templateVersion->schema_definition,
            ],
            'media' => [
                'preview_photos' => $previewPhotos,
                'photos' => $photos,
                'videos' => $videos,
                'music_url' => $musicUrl,
            ],
            'views_count' => $project->views_count,
            'published_at' => $project->published_at?->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }
}
