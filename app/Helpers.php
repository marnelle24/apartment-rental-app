<?php

use Illuminate\Support\Facades\Storage;

if (! function_exists('apartment_image_url')) {
    /**
     * Get the public URL for an apartment image path.
     * Uses the configured apartment images disk (public or s3).
     */
    function apartment_image_url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        return Storage::disk(config('filesystems.apartment_images_disk', 'public'))->url($path);
    }
}
