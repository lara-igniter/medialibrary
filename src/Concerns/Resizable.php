<?php

namespace Laraigniter\MediaLibrary\Concerns;

use Elegant\Support\Str;

trait Resizable
{
    /**
     * Method for returning specific thumbnail for model.
     *
     * @param array $data
     * @param string $type
     * @param string $attribute
     *
     * @return string
     */
    public function thumbnail(array $data, string $type, string $attribute = 'image'): string
    {
        // Return empty string if the field not found
        if (!isset($data[$attribute])) {
            return '';
        }

        // We take image from the attribute field
        $image = $data[$attribute];

        return $this->getThumbnail($image, $type);
    }

    /**
     * Generate thumbnail URL.
     *
     * @param $image
     * @param $type
     *
     * @return string
     */
    public function getThumbnail($image, $type): string
    {
        // We need to get extension type ( .jpeg , .png ...)
        $ext = pathinfo($image, PATHINFO_EXTENSION);

        // We remove an extension from file name, so we can append a thumbnail type
        $name = Str::replaceLast('.' . $ext, '', $image);

        // We merge the original name + type + extension
        return $name . '-' . $type . '.' . $ext;
    }
}
