<?php

namespace Kristories\Qrcode;

use Cache;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class Qrcode extends Field
{

    /**
     * The callback used to retrieve the QR text.
     *
     * @var callable
     */
    public $textCallback;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'qrcode';

    /**
     * Specify the callback that should be used to retrieve the QR text.
     *
     * @param  callable  $textCallback
     * @return $this
     */
    public function text(callable $textCallback)
    {
        $this->textCallback = $textCallback;

        return $this;
    }

    public function plainText($text = null)
    {
        return $this->withMeta(['text' => $text]);
    }

    public function background($background = null)
    {

        return $this->withMeta(['background' => $this->_renderImage($background)]);
    }

    public function logo($logo = null)
    {
        return $this->withMeta(['logo' => $this->_renderImage($logo)]);
    }

    protected function _renderImage($url = null)
    {
        if ($url and curl_init($url)) {
            $image = Cache::rememberForever('qr-img-' . md5($url), function () use ($url) {
                $image     = file_get_contents($url);
                $file_info = new \finfo(FILEINFO_MIME_TYPE);
                $mime_type = $file_info->buffer($image);

                return 'data: ' . $mime_type . ';base64,' . base64_encode(file_get_contents($url));
            });

            return $image;
        }

        return false;
    }

    /**
     * Resolve the QR code for the field.
     *
     * @return string|null
     */
    public function resolveQrCode()
    {
        return call_user_func($this->textCallback, $this->value, $this->resource);
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_merge(parent::jsonSerialize(), [
            'text' => $this->resolveQrCode(),
        ]);
    }
}
