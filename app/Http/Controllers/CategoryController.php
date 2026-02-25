<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\ImageKitService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    private ImageKitService $imageKit;

    public function __construct(ImageKitService $imageKit)
    {
        $this->imageKit = $imageKit;
    }

    public function index(): JsonResponse
    {
        $categories = Category::with('types')->latest()->get();
        return $this->successResponse($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'category_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status'         => 'nullable|in:1,2'
        ]);

        try {
            return DB::transaction(function () use ($request, $data) {
                if ($request->hasFile('category_image')) {
                    $data['category_image'] = $this->uploadToImageKit($request->file('category_image'));
                }

                $category = Category::create($data);
                return $this->successResponse($category, 'Category created successfully', 201);
            });
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::find($id);
        if (!$category) return $this->errorResponse('Category not found', 404);

        $data = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'category_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status'         => 'sometimes|in:1,2'
        ]);

        // If a new image is uploaded
        if ($request->hasFile('category_image')) {
            // 1. Delete old image from ImageKit
            if (!empty($category->category_image)) {
                $this->imageKit->delete($category->category_image);
            }

            // 2. Upload new image
            $data['category_image'] = $this->uploadToImageKit($request->file('category_image'));
        }

        $category->update($data);
        return $this->successResponse($category, 'Category updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::find($id);
        if (!$category) return $this->errorResponse('Category not found', 404);

        // 1. Delete image from ImageKit before deleting the category from DB
        if (!empty($category->category_image)) {
            $this->imageKit->delete($category->category_image);
        }

        $deletedData = $category;
        
        // 2. Delete from Database
        $category->delete();

        return $this->successResponse($deletedData, 'Category deleted successfully');
    }

    /** ===================== PRIVATE OOP HELPERS ===================== */

    private function uploadToImageKit($file): ?string
    {
        return $this->imageKit->upload($file, 'cat_' . time(), 'categories');
    }

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