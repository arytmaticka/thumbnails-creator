<?php

namespace App\Service;
use Throwable;

interface ImageResizerInterface
{
    /**
     * @param string $filename Filename of input image
     * @param int $maxWidth Maximal width of resized image. If less ten image stays the same.
     * @return string resized image
     * @throws Throwable
     */
    public function scaleMaxWidth(string $filename, int $maxWidth) : string;
}