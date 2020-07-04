<?php

require 'vendor/autoload.php';

use Intervention\Image\Gd\Font;
use Intervention\Image\ImageManagerStatic as Image;


class ImageService
{
    public $img;
    public $component;

    public function generate(array $data)
    {
        $this->getBg($data['bg']);
        $this->apply($data['dynamic']);
        $this->apply($data['static']);

        return $this->img->save('public/results/teste.png', 100, 'png');
    }

    public function apply($data)
    {
        foreach ($data as $this->component) {
            if (isset($this->component['img'])) {
                $imgCustom = Image::make($this->component['img']);
                $imgCustom->resize($this->component['width'] ?? $imgCustom->getWidth(), $this->component['width'] ?? $imgCustom->getHeight());
                $this->img->insert($imgCustom, $this->component['position'] ?? '', $this->component['x'], $this->component['y']);
            }

            if (isset($this->component['text'])) {
                // Lines on string
                $lines  = explode("\n", wordwrap($this->component['text'], $this->limitByWidth()));

                // Size
                $size   = $this->getSize();
                $y      = $size['height'] * 2;
                $width  = $this->maxWidth($lines) + ($size['width'] * 2);
                $height = ((count($lines) + 1) * $y) - $size['height'];

                // Content
                $txt    = Image::canvas($width, $height, $this->getRgba($this->component['bg-color']));

                foreach ($lines as $line) {
                    $txt->text($line, $size['width'], $y, function ($font) {
                        $font->file($this->component['font']);
                        $font->size($this->component['size']);
                        $font->color($this->component['color']);
                    });

                    $y += $size['height'] * 2;
                }

                $this->img->insert($txt, $this->component['position'] ?? '', $this->component['x'], $this->component['y']);
            }
        }
    }

    public function getSize(string $text = '0'): array
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

    public function getBg(array $bg): ImageService
    {
        $this->img = isset($bg['img'])
            ? Image::make($bg['img'])
            : Image::canvas($bg['width'], $bg['height'], $this->getRgba($bg['color']));

        if (isset($bg['border'])) $this->setPadding($bg['border']);

        return $this;
    }

    public function limitByWidth(): int
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

    public function setPadding($pad)
    {
        $size = $pad['size'];
        $color = $this->getRgba($pad['color']);
        return $this->img->resizeCanvas($this->img->width() + $size, $this->img->height() + $size, 'center', false, $color);
    }
}

$post     = json_decode(file_get_contents('./public/model/posts/first.json'), true);
$template = json_decode(file_get_contents($post['template']), true);
$json     = array_replace_recursive($template, $post);

$obj  = (new ImageService())->generate($json);
$path = 'public/results/teste.png';

echo $obj->response();
