<?php

require 'vendor/autoload.php';

use Intervention\Image\Gd\Font;
use Intervention\Image\ImageManagerStatic as Image;


class ImageService
{
    public function generate(array $data)
    {
        $img = $this->getBg($data['bg']);

        foreach ($data['components'] as $component) {
            if (isset($component['img'])) {
                $imgCustom = Image::make($component['img']);
                $imgCustom->resize($component['width'] ?? $imgCustom->getWidth(), $component['width'] ?? $imgCustom->getHeight());
                $img->insert($imgCustom, $component['position'] ?? '', $component['x'], $component['y']);
            }

            if (isset($component['text'])) {
                $size   = $this->getSize($component['size'], $component['font']);
                $lines  = explode("\n", wordwrap($component['text'], 30));
                $y      = $size['height'] * 2;
                $width  = $this->maxWidth($lines, $component['size'], $component['font']) + ($size['width'] * 2);
                $height = ((count($lines) + 1) * $y) - $size['height'];

                $txt    = Image::canvas($width, $height, $this->getRgba($component['bg-color']));

                foreach ($lines as $line) {
                    $txt->text($line, $size['width'], $y, function ($font) use ($component) {
                        $font->file($component['font']);
                        $font->size($component['size']);
                        $font->color($component['color']);
                    });

                    $y += $size['height'] * 2;
                }

                $img->insert($txt, $component['position'] ?? '', $component['x'], $component['y']);
            }

        }

        return $img->save('public/results/teste.png', 100, 'png');
    }

    public function getSize($size, $file, $text = '0')
    {
        $font = new Font($text);
        $font->file($file);
        $font->size($size);

        return $font->getBoxSize();
    }

    public function maxWidth(array $lines, int $size, string $file): int
    {
        $widths = [];

        foreach ($lines as $line) {
            $widths[] = $this->getSize($size, $file, $line)['width'];
        }

        return max($widths);
    }

    public function getRgba($hex): array
    {
        $color = explode(' ', $hex);
        $rgba = sscanf($color[0], '#%2x%2x%2x');
        $rgba[] = $color[1] ?? 0;
        return $rgba;
    }

    public function getBg($bg)
    {
        if (is_array($bg)) {
            return Image::canvas($bg['width'], $bg['height'], $bg['color']);
        }

        return Image::make($bg);
    }
}

$template = json_decode(file_get_contents('./public/model/templates/first.json'), true);
$post     = json_decode(file_get_contents('./public/model/posts/first.json'), true);
$json     = array_replace_recursive($template, $post);

$obj  = (new ImageService())->generate($json);
$path = 'public/results/teste.png';

echo $obj->response();
