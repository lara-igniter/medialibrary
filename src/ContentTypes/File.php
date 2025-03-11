<?php

namespace Laraigniter\MediaLibrary\ContentTypes;

use Elegant\Support\Collection;
use Elegant\Support\Facades\Storage;

class File extends BaseType
{
    /**
     * @return \Elegant\Support\Collection
     */
    public function handle(): Collection
    {
        $path = $this->slug . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('n') . DIRECTORY_SEPARATOR;

        if(!is_null($this->oldFilePath)) {
            $this->deleteOldFile($this->oldFilePath);
        }

        $filename = $this->generateFileName($this->file, $path);

        $stored = Storage::disk(config('media.storage.disk'))->put(
            $path . $filename . '.' . $this->file->getClientOriginalExtension(),
            $this->file->getContent(),
        );

        if ($stored) {
            $fullPath = $path . $filename . '.' . $this->file->getClientOriginalExtension();

            return new Collection([
                'file_path' => $fullPath,
                'file_name' => $this->file->getClientOriginalName(),
                'file_type' => $this->type,
                'file_extension' => $this->file->getClientOriginalExtension(),
                'file_size' => $this->file->getSize(),
            ]);
        }

        return new Collection();
    }
}
