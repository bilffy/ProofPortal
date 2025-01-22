<?php

namespace App\Http\Livewire\Photography;

use App\Services\ImageService;
use Livewire\Component;

class ImageFrame extends Component
{
    public $imageId;
    public $name;
    public $landscape;

    public function mount($imageId, $name, $landscape)
    {
        $this->imageId = $imageId;
        $this->name = $name;
        $this->landscape = $landscape;
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
