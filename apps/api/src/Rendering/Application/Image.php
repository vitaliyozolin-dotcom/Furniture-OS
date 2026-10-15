<?php

declare(strict_types=1);

namespace App\Rendering\Application;

final readonly class Image
{
    public function __construct(public string $bytes)
    {
        $dimensions = @getimagesizefromstring($bytes);
        if (strlen($bytes) > 10485760 || $dimensions === false || $dimensions[2] !== IMAGETYPE_PNG || $dimensions[0] * $dimensions[1] > 16777216) {
            throw new \InvalidArgumentException('Expected a PNG image up to 10 MiB and 16 megapixels.');
        }
    }
}
