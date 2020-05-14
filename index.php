<?php

require 'vendor/autoload.php';

use Intervention\Image\Gd\Font;
use Intervention\Image\ImageManagerStatic as Image;


class ImageService
{
    public $component;

    public function generate(array $data)
    {
        $img = $this->getBg($data['bg']);

        foreach ($data['components'] as $this->component) {
            if (isset($this->component['img'])) {
                $imgCustom = Image::make($this->component['img']);
                $imgCustom->resize($this->component['width'] ?? $imgCustom->getWidth(), $this->component['width'] ?? $imgCustom->getHeight());
                $img->insert($imgCustom, $this->component['position'] ?? '', $this->component['x'], $this->component['y']);
            }

            if (isset($this->component['text'])) {
                $size   = $this->getSize();

                $limit  = $this->limitByWidth();
                $lines  = explode("\n", wordwrap($this->component['text'], $limit ));
                $y      = $size['height'] * 2;
                $width  = $this->maxWidth($lines) + ($size['width'] * 2);
                $height = ((count($lines) + 1) * $y) - $size['height'];

                $txt    = Image::canvas($width, $height, $this->getRgba($this->component['bg-color']));

                foreach ($lines as $line) {
                    $txt->text($line, $size['width'], $y, function ($font) {
                        $font->file($this->component['font']);
                        $font->size($this->component['size']);
                        $font->color($this->component['color']);
                    });

                    $y += $size['height'] * 2;
                }

                $img->insert($txt, $this->component['position'] ?? '', $this->component['x'], $this->component['y']);
            }

        }

        return $img->save('public/results/teste.png', 100, 'png');
    }

    public function getSize($text = '0')
    {
        $font = new Font($text);
        $font->file($this->component['font']);
        $font->size($this->component['size']);

        return $font->getBoxSize();
    }

    public function maxWidth(array $lines): int
    {
        $widths = [];

        foreach ($lines as $line) {
            $widths[] = $this->getSize($line)['width'];
        }

        return max($widths);
    }

    public function getRgba($hex): array
    {
        if (!$hex) return [0,0,0,0];
        $color = explode(' ', $hex);
        $rgba = sscanf($color[0], '#%2x%2x%2x');
        $rgba[] = $color[1] ?? 1;
        return $rgba;
    }

    public function getBg($bg)
    {
        if (is_array($bg)) {
            return Image::canvas($bg['width'], $bg['height'], $this->getRgba($bg['color']));
        }

        return Image::make($bg);
    }

    public function limitByWidth()
    {
        $str = '';

        foreach (str_split($this->component['text']) as $letter) {
            $str .= $letter;

            $box = $this->getSize($str);
            if ($box['width'] >= $this->component['width']) {
                return strlen($str);
            }
        }
    }
}

$template = json_decode(file_get_contents('./public/model/templates/second.json'), true);
$post     = json_decode(file_get_contents('./public/model/posts/second.json'), true);
$json     = array_replace_recursive($template, $post);

$obj  = (new ImageService())->generate($json);
$path = 'public/results/teste.png';

echo $obj->response();
