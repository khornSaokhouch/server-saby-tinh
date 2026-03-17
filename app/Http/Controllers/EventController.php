<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\ImageKitService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    private ImageKitService $imageKit;

    public function __construct(ImageKitService $imageKit)
    {
        $this->imageKit = $imageKit;
    }

    /** =================== INDEX =================== */
    public function index(): JsonResponse
    {
        $events = Event::with('promotion')->latest()->get();
        return $this->successResponse($events, 'Events retrieved successfully');
    }

    /** =================== SHOW =================== */
    public function show($idOrName): JsonResponse
    {
        try {
            // Try by ID first
            $event = is_numeric($idOrName) ? Event::find($idOrName) : null;
            
            // If not found by ID or if not numeric, try by name (slugified comparison)
            if (!$event) {
                // We'll search for the name. Since we don't have a slug column, 
                // we'll use a broad search or exact match if preferred.
                // For performance, let's assume valid name match.
                $event = Event::where('name', 'like', str_replace('-', ' ', $idOrName))->first();
            }

            if (!$event) {
                return $this->errorResponse('Event not found', 404);
            }

            return $this->successResponse($event, 'Event details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /** =================== STORE =================== */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'promotion_id'=> 'required|exists:promotions,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date',
            'status'      => 'nullable|string|in:draft,scheduled,active,expired',
            'event_image' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
        ]);

        try {
            return DB::transaction(function () use ($request, $data) {

                // Upload image via ImageKit if present
                if ($request->hasFile('event_image')) {
                    $data['event_image'] = $this->uploadToImageKit($request->file('event_image'));
                }

                $event = Event::create($data);
                return $this->successResponse($event, 'Event created successfully', 201);
            });
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /** =================== UPDATE =================== */
    public function update(Request $request, int $id): JsonResponse
    {
        $event = Event::find($id);
        if (!$event) return $this->errorResponse('Event not found', 404);

        $data = $request->validate([
            'promotion_id'=> 'sometimes|exists:promotions,id',
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date',
            'status'      => 'nullable|string|in:draft,scheduled,active,expired',
            'event_image' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
        ]);

        // Handle image update
        if ($request->hasFile('event_image')) {
            // Delete old image from ImageKit
            if (!empty($event->event_image)) {
                $this->imageKit->delete($event->event_image);
            }

            // Upload new image
            $data['event_image'] = $this->uploadToImageKit($request->file('event_image'));
        }

        $event->update($data);
        return $this->successResponse($event, 'Event updated successfully');
    }

    /** =================== DESTROY =================== */
    public function destroy(int $id): JsonResponse
    {
        $event = Event::find($id);
        if (!$event) return $this->errorResponse('Event not found', 404);

        // Delete image from ImageKit
        if (!empty($event->event_image)) {
            $this->imageKit->delete($event->event_image);
        }

        $event->delete();
        return $this->successResponse($event, 'Event deleted successfully');
    }

    /** =================== PRIVATE HELPERS =================== */
    
    private function uploadToImageKit($file): ?string
    {
        return $this->imageKit->upload($file, 'event_' . time(), 'events');
    }

    /** =================== RESPONSE HELPERS =================== */
    
    protected function successResponse($data, $message = 'Success', $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data
        ], $status);
    }

    protected function errorResponse($message = null, $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $status);
    }
}
