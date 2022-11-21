<?php

namespace App\Services;

use Image;

class ImageService
{
    protected $image;
    protected $height;
    protected $width;

    /**
     * ImageService constructor.
     * @param $imagePath
     */
    public function __construct($imagePath)
    {
        $this->image = $this->makeImage($imagePath);
    }

    /**
     * @param $imagePath
     * @return \Intervention\Image\Image
     */
    public function makeImage($imagePath)
    {
        $image = Image::make($imagePath);
        $this->height = $image->height();
        $this->width = $image->width();
        return $image;
    }

    /** 缩放图片使其不超过限定宽高
     * @param $path
     * @param $size
     * @return \Intervention\Image\Image
     */
    public function zoomImage($path, $size)
    {
        $size = strtolower($size);
        list($width, $height) = explode('x', $size);

        if ($width && $width < 0) $width = null;
        if ($height && $height < 0) $height = null;

        $orgWidth = $this->width;
        $orgHeight = $this->height;

        if (!$width) {
            $width = $orgWidth;
        }
        if (!$height) {
            $height = $orgHeight;
        }

        if ($orgHeight <= $height && $orgWidth <= $width) {
            $rate = 1;
        } else {
            $rate = min($width / $orgWidth, $height / $orgHeight);
        }

        $destWidth = intval(floor($orgWidth * $rate));
        $destHeight = intval(floor($orgHeight * $rate));

        $image = $this->image->backup();

        $image->resize($destWidth, $destHeight)->save($path);

        return $image;
    }
}