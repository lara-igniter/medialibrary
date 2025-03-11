<?php

namespace Laraigniter\MediaLibrary\Concerns;

use Elegant\Foundation\Http\File\UploadedFile;
use Elegant\Support\Collection;
use Elegant\Support\Facades\Storage;
use Elegant\Support\Str;
use Laraigniter\MediaLibrary\Uploader;

trait Uploadable
{
    use Resizable;

    /**
     * @param \Elegant\Foundation\Http\File\UploadedFile $file
     * @param string $slug
     * @param string|null $oldFilePath
     *
     * @return \Elegant\Support\Collection
     */
    public function uploadFile(UploadedFile $file, string $slug, string $oldFilePath = null): Collection
    {
        $type = $this->checkExtension($file->getClientOriginalExtension());

        return Uploader::upload($file, $slug, $oldFilePath, $type);
    }

    /**
     * @param array $files
     * @param string $slug
     * @param string|null $oldFilePath
     *
     * @return \Elegant\Support\Collection
     */
    public function uploadMultipleFile(array $files, string $slug, string $oldFilePath = null): Collection
    {
        $multipleFiles = collect();

        foreach ($files as $file) {
            $type = $this->checkExtension($file->getClientOriginalExtension());

            $multipleFiles->push(Uploader::upload($file, $slug, $oldFilePath, $type));
        }

        return $multipleFiles;
    }

    /**
     * Get model file path check if exist in public storage and move in temporary storage
     *
     * @param $model
     * @param string $field_name
     * @param int $id
     * @return void
     * @throws \Exception
     */
    public function temporaryFile($model, string $field_name, int $id)
    {
        $file = (new $model)->findOrFail($id);

        if (Storage::exists($file->{$field_name})) {
            $this->moveToTemp($file->{$field_name});
        }

        if (!empty(config('media.settings.thumbnails'))) {
            $collection_thumbnails = json_encode(config('media.settings.thumbnails'));

            foreach (json_decode($collection_thumbnails) as $thumbnails) {
                $thumbImage = $this->getThumbnail($file->{$field_name}, $thumbnails->name);

                if (Storage::exists($thumbImage)) {
                    $this->moveToTemp($thumbImage);
                }
            }
        }
    }

    /**
     * Get model file path check if exist in temporary storage and restore to public storage
     *
     * @param $model
     * @param string $field_name
     * @param int $id
     * @return void
     * @throws \Exception
     */
    public function restoreFile($model, string $field_name, int $id)
    {
        $file = (new $model)->withTrashed()->findOrFail($id);

        if (Storage::disk(config('media.storage.temporary'))->exists('tmp/' . $file->{$field_name})) {
            $this->moveToPublic($file->{$field_name});
        }

        if (!empty(config('media.settings.thumbnails'))) {
            $collection_thumbnails = json_encode(config('media.settings.thumbnails'));

            foreach (json_decode($collection_thumbnails) as $thumbnails) {
                $thumbImage = $this->getThumbnail($file->{$field_name}, $thumbnails->name);

                if (Storage::disk(config('media.storage.temporary'))->exists('tmp/' . $thumbImage)) {
                    $this->moveToPublic($thumbImage);
                }
            }
        }
    }

    /**
     * Delete file from storage & add to temp
     *
     * @param $path
     * @return void
     * @throws \Exception
     */
    public function moveToTemp($path)
    {
        try {
            $original = Storage::disk(config('media.storage.disk'))->get($path);

            $put = Storage::disk(config('media.storage.temporary'))->put('tmp/' . $path, $original);

            if ($put) {
                Storage::delete($path);
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Delete file from temp directory & add to public storage
     *
     * @param $path
     * @return void
     * @throws \Exception
     */
    public function moveToPublic($path)
    {
        try {
            $original = Storage::disk(config('media.storage.temporary'))->get('tmp/' . $path);

            $put = Storage::disk(config('media.storage.disk'))->put($path, $original);

            if ($put) {
                Storage::disk(config('media.storage.temporary'))->delete('tmp/' . $path);
            }
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * @param $ext
     * @return string
     */
    private function checkExtension($ext): string
    {
        $type = 'file';

        if (in_array(Str::lower($ext), config('media.settings.file_extensions.image'))) {
            $type = 'image';
        }

        return $type;
    }
}
