<?php

namespace App\Http\Livewire\Photography;

use App\Services\ImageService;
use Livewire\Component;
use App\Helpers\PhotographyHelper;

class ImageFrame extends Component
{
    public $imageId;
    public $name;
    public $landscape;
    public $folderName;
    public $hasImage = false;
    public $isLightbox = false;
    public $isUploaded = false;
    public $image = '';

    protected $listeners = [
        PhotographyHelper::EV_IMAGE_UPLOADED => 'updateImage',
        PhotographyHelper::EV_IMAGE_DELETED => 'removeImage',
    ];

    public function mount($imageId, $name, $landscape, $folderName, $isUploaded = false, $isLightbox = false)
    {
        $this->imageId = $imageId;
        $this->name = $name;
        $this->landscape = $landscape;
        $this->folderName = $folderName;
        $this->isUploaded = $isUploaded;
        $this->isLightbox = $isLightbox;

        $imageService = new ImageService();
        $this->hasImage = $imageService->getIsImageFound(base64_decode(base64_decode($imageId)));
    }

    public function placeholder()
    {
        $this->image = '';
        return view('livewire.photography.image-frame');
    }

    public function updateImage($key)
    {
        if ($key && $key === $this->imageId) {
            $this->isUploaded = true;
            $this->hasImage = true;
            $this->dispatch('image-frame-updated', ['imageId' => $this->imageId, 'isLightbox' => $this->isLightbox]);
        }
    }

    public function removeImage($key)
    {
        if ($key && $key === $this->imageId) {
            $this->isUploaded = false;
            $this->hasImage = false;
            $this->dispatch('image-frame-updated', ['imageId' => $this->imageId, 'isLightbox' => $this->isLightbox]);
        }
    }

    public function rendered($view, $html)
    {
        $this->dispatch('image-frame-updated', ['imageId' => $this->imageId, 'isLightbox' => $this->isLightbox]);
    }

    public function render()
    {
        $imageService = new ImageService();
        $key = base64_decode(base64_decode($this->imageId));
        $imageContent = $imageService->getImageContent($key);
        $this->image = base64_encode($imageContent);

        return view('livewire.photography.image-frame');
    }
}
