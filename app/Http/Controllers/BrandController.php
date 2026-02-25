<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ImageKitService;

class BrandController extends Controller
{
    protected $imageKit;

    public function __construct(ImageKitService $imageKit) {
        $this->imageKit = $imageKit;
    }

    public function index(): JsonResponse {
        return $this->successResponse(Brand::all());
    }

    public function store(Request $request): JsonResponse {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'image'  => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'nullable|in:0,1'
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadToImageKit($request->file('image'));
        }

        $brand = Brand::create($data);
        return $this->successResponse($brand, 'Brand created', 201);
    }

    public function update(Request $request, int $id): JsonResponse {
        $brand = Brand::find($id);
        if (!$brand) return $this->errorResponse('Brand not found', 404);

        $data = $request->validate([
            'name'   => 'sometimes|string|max:255',
            'image'  => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'sometimes|in:0,1'
        ]);

        if ($request->hasFile('image')) {
            if ($brand->image) {
                $this->imageKit->delete($brand->image);
            }

            $data['image'] = $this->uploadToImageKit($request->file('image'));
        }

        $brand->update($data);
        return $this->successResponse($brand, 'Brand updated');
    }

    public function destroy(int $id): JsonResponse {
        $brand = Brand::find($id);
        if (!$brand) return $this->errorResponse('Brand not found', 404);
        
        if ($brand->image) {
            $this->imageKit->delete($brand->image);
        }

        $old = $brand;
        $brand->delete();
        return $this->successResponse($old, 'Brand deleted');
    }

    private function uploadToImageKit($file): ?string
    {
        return $this->imageKit->upload($file, 'brand_' . time(), 'brands');
    }
}
