<?php

namespace App\Http\Livewire\Photography;

use App\Services\ImageService;
use Livewire\Component;

class ImageFrame extends Component
{
    public $imageId;
    public $name;
    public $landscape;
    public $folderName;
    public $hasImage = false;

    public function mount($imageId, $name, $landscape, $folderName)
    {
        $this->imageId = $imageId;
        $this->name = $name;
        $this->landscape = $landscape;
        $this->folderName = $folderName;

        $imageService = new ImageService();
        $this->hasImage = $imageService->getIsImageFound(base64_decode(base64_decode($imageId)));
    }

    public function placeholder()
    {
        return view('livewire.photography.image-frame', ['image' => '']);
    }

    public function rendered($view, $html)
    {
        $this->dispatch('image-frame-updated', ['imageId' => $this->imageId]);
    }

    public function render()
    {
        $imageService = new ImageService();
        $key = base64_decode(base64_decode($this->imageId));
        $imageContent = $imageService->getImageContent($key);

        return view('livewire.photography.image-frame', ['image' => base64_encode($imageContent)]);
    }
}
