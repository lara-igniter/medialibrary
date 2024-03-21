<?php

namespace Laraigniter\MediaLibrary\ContentTypes;

use Elegant\Support\Collection;
use Elegant\Support\Facades\Storage;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;

class Image extends BaseType
{
    /**
     * @return Collection
     */
    public function handle(): Collection
    {
        $file = $this->request->file($this->field);

        $resize_quality = !empty(config('media.settings.quality')) ? config('media.settings.quality') : '75%';

        $path = $this->slug . DIRECTORY_SEPARATOR . date('FY') . DIRECTORY_SEPARATOR;

        $filename = $this->generateFileName($file, $path);

        $this->config = array_merge($this->config, [
            'file_name' => $filename,
            'quality' => $resize_quality,
            'upload_path' => storage_path('app/public/') . $path,
        ]);

        app('load')->library('upload', $this->config);

        $this->deleteOldFile($this->oldFilePath);

        $data = collect();

        if (app('upload')->do_upload($this->field)) {
            $uploadData = app('upload')->data();

            $fullPath = $path . $filename . '.' . $file->getClientOriginalExtension();

            $manager = new ImageManager(['driver' => config('media.settings.library')]);

            $image = $manager->make(storage_path('app/public/' . $fullPath))->orientate();

            $data = collect([
                'file_path' => $fullPath,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $this->type,
                'file_extension' => $image->extension,
                'file_size' => $image->filesize(),
            ]);

            $resize_width = null;
            $resize_height = null;

            if (!empty(config('media.settings.resize'))
                && (!is_null(config('media.settings.resize.width'))
                    || !is_null(config('media.settings.resize.height')))) {
                if (!is_null(config('media.settings.resize.width'))) {
                    $resize_width = config('media.settings.resize.width');
                }
                if (!is_null(config('media.settings.resize.height'))) {
                    $resize_height = config('media.settings.resize.height');
                }
            } else {
                $resize_width = $uploadData['image_width'];
                $resize_height = $uploadData['image_height'];
            }

            $image = $image->resize(
                $resize_width,
                $resize_height,
                function (Constraint $constraint) {
                    $constraint->aspectRatio();
                    if (!is_null(config('media.settings.upsize')) && !config('media.settings.upsize')) {
                        $constraint->upsize();
                    }
                }
            );

            $image->encode($image->extension, $resize_quality);

            Storage::disk(config('media.storage.disk'))->put($fullPath, (string)$image);

            /**
             * Add thumbnail logic
             */
            if (!empty(config('media.settings.thumbnails'))) {
                $collection_thumbnails = json_encode(config('media.settings.thumbnails'));

                foreach (json_decode($collection_thumbnails) as $thumbnails) {
                    if (isset($thumbnails->name) && isset($thumbnails->scale)) {
                        $image = $manager->make(storage_path('app/public/' . $fullPath));

                        $scale = $thumbnails->scale;
                        $thumb_resize_width = $image->width();
                        $thumb_resize_height = $image->height();

                        if ($thumb_resize_width != null && $thumb_resize_width != 'null') {
                            $thumb_resize_width = $thumb_resize_width * intval($scale);
                        }

                        if ($thumb_resize_height != null && $thumb_resize_height != 'null') {
                            $thumb_resize_height = $thumb_resize_height * intval($scale);
                        }

                        $image->resize(
                            $thumb_resize_width,
                            $thumb_resize_height,
                            function (Constraint $constraint) {
                                $constraint->aspectRatio();
                                if (!is_null(config('media.settings.upsize')) && !config('media.settings.upsize')) {
                                    $constraint->upsize();
                                }
                            }
                        )->encode($image->extension, $resize_quality);

                        Storage::disk(config('media.storage.disk'))->put(
                            $path . $filename . '-' . $thumbnails->name . '.' . $image->extension,
                            (string)$image
                        );
                    } elseif (isset($thumbnails->crop->width) && isset($thumbnails->crop->height)) {
                        if (isset($thumbnails->resize_canvas)) {
                            $dimension = intval($thumbnails->resize_canvas->dimension);

                            $vertical = $resize_width < $resize_height;
                            $horizontal = $resize_width > $resize_height;
                            $square = (bool)(($resize_width = $resize_height));

                            $image = $manager->make(storage_path('app/public/' . $fullPath));

                            if ($vertical) {
                                $top = $bottom = 0;
                                $newHeight = ($dimension) - ($bottom + $top);
                                $image->resize(null, $newHeight, function (Constraint $constraint) {
                                    $constraint->aspectRatio();
                                    if (!is_null(config('media.settings.upsize')) && !config('media.settings.upsize')) {
                                        $constraint->upsize();
                                    }
                                });

                            } else if ($horizontal) {
                                $right = $left = 0;
                                $newWidth = ($dimension) - ($right + $left);
                                $image->resize($newWidth, null, function (Constraint $constraint) {
                                    $constraint->aspectRatio();
                                    if (!is_null(config('media.settings.upsize')) && !config('media.settings.upsize')) {
                                        $constraint->upsize();
                                    }
                                });
                            } else if ($square) {
                                $right = $left = 0;
                                $newWidth = ($dimension) - ($left + $right);
                                $image->resize($newWidth, null, function (Constraint $constraint) {
                                    $constraint->aspectRatio();
                                    if (!is_null(config('media.settings.upsize')) && !config('media.settings.upsize')) {
                                        $constraint->upsize();
                                    }
                                });
                            }

                            $image->resizeCanvas(
                                $dimension, $dimension, 'center', false, $thumbnails->resize_canvas->bg_color
                            )->encode($image->extension, $resize_quality);

                        } else {
                            $crop_width = $thumbnails->crop->width;
                            $crop_height = $thumbnails->crop->height;

                            $image = $manager->make(storage_path('app/public/' . $fullPath));

                            $image->orientate()
                                ->fit($crop_width, $crop_height)
                                ->encode($image->extension, $resize_quality);

                        }

                        Storage::disk(config('media.storage.disk'))->put(
                            $path . $filename . '-' . $thumbnails->name . '.' . $image->extension,
                            (string)$image
                        );
                    }
                }
            }
        }

        return $data;
    }
}
