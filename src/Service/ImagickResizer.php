<?php

namespace App\Service;
use Imagick;

class ImagickResizer implements ImageResizerInterface
{
    /**
     * @inheritdoc
     */
    public function scaleMaxWidth(string $filename, int $maxWidth): string
    {
        $img = new Imagick($filename);
        if ($img->getImageWidth() > $maxWidth) {
            $img->scaleImage($maxWidth, 0);
        }
        $data = $img->getImageBlob();
        $img->clear();

        return $data;
    }
}