<?php

namespace Laraigniter\MediaLibrary;

use Elegant\Foundation\Http\File\UploadedFile;
use Elegant\Support\Collection;
use Laraigniter\MediaLibrary\ContentTypes\File;
use Laraigniter\MediaLibrary\ContentTypes\Image;

class Uploader
{
    /**
     * @param \Elegant\Foundation\Http\File\UploadedFile $file
     * @param string $slug
     * @param $oldFilePath
     * @param string $type
     *
     * @return \Elegant\Support\Collection
     */

    public static function upload(
        UploadedFile $file,
        string $slug,
        $oldFilePath,
        string $type
    ): Collection
    {
        switch ($type) {
            /********** FILE TYPE **********/
            case 'file':
                return (new File($file, $slug, $oldFilePath, $type))->handle();
            /********** IMAGE TYPE **********/
            case 'image':
                return (new Image($file, $slug, $oldFilePath, $type))->handle();
            /********** DEFAULT TYPE **********/
            default:
                return (new Image($file, $slug, $oldFilePath, 'image'))->handle();
        }
    }
}
