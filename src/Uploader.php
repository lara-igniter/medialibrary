<?php

namespace Laraigniter\MediaLibrary;

use Elegant\Support\Collection;
use Laraigniter\MediaLibrary\ContentTypes\File;
use Laraigniter\MediaLibrary\ContentTypes\Image;

class Uploader
{
    /**
     * @param \MY_Input $request
     * @param string $field
     * @param string $slug
     * @param array $row
     * @param $oldFilePath
     * @param string $type
     * @return \Elegant\Support\Collection
     */

    public static function upload(\MY_Input $request, string $field, string $slug, array $row, $oldFilePath, string $type): Collection
    {
        switch ($type) {
            /********** FILE TYPE **********/
            case 'file':
                return (new File($request, $field, $slug, $row, $oldFilePath, $type))->handle();
            /********** IMAGE TYPE **********/
            case 'image':
                return (new Image($request, $field, $slug, $row, $oldFilePath, $type))->handle();
            /********** DEFAULT TYPE **********/
            default:
                return (new Image($request, $field, $slug, $row, $oldFilePath, 'image'))->handle();
        }
    }
}
