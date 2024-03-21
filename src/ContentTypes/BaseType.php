<?php

namespace Laraigniter\MediaLibrary\ContentTypes;

use Elegant\Foundation\Http\File\UploadedFile;
use Elegant\Support\Collection;
use Elegant\Support\Facades\Storage;
use Elegant\Support\Str;

abstract class BaseType
{
    /**
     * @var \MY_Input
     */
    protected \MY_Input $request;

    /**
     * @var string
     */
    protected string $field;

    /**
     * @var string
     */
    protected string $slug;

    /**
     * @var array
     */
    protected array $row;


    /**
     * @var ?string
     */
    protected ?string $oldFilePath;

    /**
     * @var string
     */
    protected string $type;

    /**
     * @var array
     */
    protected array $config;

    /**
     * BaseType constructor.
     *
     * @param \MY_Input $request
     * @param string $field
     * @param string $slug
     * @param array $row
     * @param $oldFilePath
     * @param string $type
     */
    public function __construct(\MY_Input $request, string $field, string $slug, array $row, $oldFilePath, string $type)
    {
        $this->request = $request;
        $this->field = $field;
        $this->slug = $slug;
        $this->row = $row;
        $this->oldFilePath = $oldFilePath;
        $this->type = $type;
        $this->config = [
            'allowed_types' => config('media.settings.allowed_types'),
            'max_size' => config('media.settings.max_size'),
            'remove_spaces' => config('media.settings.remove_spaces'),
            'encrypt_name' => config('media.settings.encrypt_name'),
            'overwrite' => config('media.settings.overwrite')
        ];
    }

    /**
     * @return \Elegant\Support\Collection
     */
    abstract public function handle(): Collection;

    /**
     * @param \Elegant\Foundation\Http\File\UploadedFile $file
     * @param string $path
     *
     * @return string
     */
    protected function generateFileName(UploadedFile $file, string $path): string
    {
        if (!is_null(config('media.settings.preserve_file_upload_name')) && config('media.settings.preserve_file_upload_name')) {
            $filename = basename($file->getClientOriginalName(), '.' . $file->getClientOriginalExtension());
            $filename_counter = 1;

            // Make sure the filename does not exist, if it does make sure to add a number to the end 1, 2, 3, etc...
            while (Storage::exists($path . $filename . '.' . $file->getClientOriginalExtension())) {
                $filename = basename($file->getClientOriginalName(), '(' . $filename_counter++ . ').' . $file->getClientOriginalExtension());
            }
        } else {
            $filename = bin2hex(app('encryption')->create_key(10));

            // Make sure the filename does not exist, if it does, just regenerate
            while (file_exists($path . $filename . '.' . $file->getClientOriginalExtension())) {
                // Make sure if delete old is true replace old file with new one
                if (!is_null(config('media.settings.delete_old')) && config('media.settings.delete_old')) {
                    Storage::delete($path . $filename . '.' . $file->getClientOriginalExtension());
                }

                $filename = bin2hex(app('encryption')->create_key(10));
            }
        }

        return $filename;
    }

    /**
     * Delete old file if exist and its enable in config
     * @param $path
     * @return void
     */
    protected function deleteOldFile($path)
    {
        // Make sure that config file have the field delete_old
        if (!is_null(config('media.settings.delete_old')) && config('media.settings.delete_old')) {
            // Make sure the filename does not exist, if it does make sure to remove it.
            if (Storage::exists($path)) {
                Storage::delete($path);
            }

            if (!empty(config('media.settings.thumbnails'))) {
                $collection_thumbnails = json_encode(config('media.settings.thumbnails'));

                foreach (json_decode($collection_thumbnails) as $thumbnails) {
                    $ext = pathinfo($path, PATHINFO_EXTENSION);

                    $name = Str::replaceLast('.' . $ext, '', $path);

                    $thumbnail = $name . '-' . $thumbnails->name . '.' . $ext;

                    if (Storage::exists($thumbnail)) {
                        Storage::delete($thumbnail);
                    }
                }
            }
        }
    }
}
