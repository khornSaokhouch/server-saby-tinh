<?php

namespace App\Services;

use ImageKit\ImageKit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ImageKitService
{
    private ImageKit $imagekit;

    public function __construct()
    {
        $this->imagekit = new ImageKit(
            env('IMAGEKIT_PUBLIC_KEY'),
            env('IMAGEKIT_PRIVATE_KEY'),
            env('IMAGEKIT_URL_ENDPOINT')
        );
    }

    public function upload($file, string $fileName, string $folder = '/'): ?string
    {
        try {
            $fileContent = $file instanceof UploadedFile
                ? file_get_contents($file->getRealPath())
                : $file;

            $result = $this->imagekit->uploadFiles([
                'file'     => base64_encode($fileContent),
                'fileName' => $fileName,
                'folder'   => $folder,
            ]);

            if ($result && isset($result->result->url)) {
                return $result->result->url;
            }

            return null;
        } catch (\Exception $e) {
            Log::error("ImageKit Upload Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Corrected Delete Method
     */
    public function delete(?string $fileUrl): void
    {
        if (!$fileUrl) return;

        try {
            // 1. Get just the file name from the URL (e.g., "cat_1700000.jpg")
            $fileName = basename(parse_url($fileUrl, PHP_URL_PATH));

            // 2. Search ImageKit for this exact file name using the correct method
            $files = $this->imagekit->listFiles([
                'searchQuery' => 'name="' . $fileName . '"'
            ]);

            // 3. Extract the fileId and delete it
            if (isset($files->result) && !empty($files->result)) {
                $fileId = $files->result[0]->fileId;
                $this->imagekit->deleteFile($fileId);
            }
        } catch (\Exception $e) {
            Log::error("ImageKit Delete Error: " . $e->getMessage());
        }
    }
}