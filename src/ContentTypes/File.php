<?php

namespace Laraigniter\MediaLibrary\ContentTypes;

use Elegant\Support\Collection;

class File extends BaseType
{
    /**
     * @return Collection
     */
    public function handle(): Collection
    {
        $file = $this->request->file($this->field);

        $path = $this->slug . DIRECTORY_SEPARATOR . date('FY') . DIRECTORY_SEPARATOR;

        $filename = $this->generateFileName($file, $path);

        $this->config = array_merge($this->config, [
            'file_name' => $filename,
            'upload_path' => storage_path('app/public/') . $path,
        ]);

        app('load')->library('upload', $this->config);

        $this->deleteOldFile($this->oldFilePath);

        $data = collect();

        if (app('upload')->do_upload($this->field)) {
            $fullPath = $path . $filename . '.' . $file->getClientOriginalExtension();

            $data = collect([
                'file_path' => $fullPath,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $this->type,
                'file_extension' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
            ]);
        }

        return $data;
    }
}
