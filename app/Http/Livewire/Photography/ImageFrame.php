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
    // public $image = ''; //code by chromedia
    public $externalSubjectId = null;
    public $category = null;

    protected $listeners = [
        PhotographyHelper::EV_IMAGE_UPLOADED => 'updateImage',
        PhotographyHelper::EV_IMAGE_DELETED => 'removeImage',
    ];

    public function mount($imageId, $name, $landscape, $folderName, $hasImage = false, $isUploaded = false, $isLightbox = false, $externalSubjectId = null, $category = null)
    {
        $this->imageId = $imageId;
        $this->name = $name;
        $this->landscape = $landscape;
        $this->folderName = $folderName;
        $this->isUploaded = $isUploaded;
        $this->isLightbox = $isLightbox;
        $this->externalSubjectId = $externalSubjectId;
        $this->hasImage = $hasImage;
        $this->category = $category;
    }

    public function placeholder()
    {
        // $this->image = ''; //code by chromedia
        // return view('livewire.photography.image-frame'); //code by chromedia
        return view('livewire.photography.image-frame', ['image' => '']); //code by IT
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

    // code by chromedia
    // public function render()
    // {
    //     $imageService = new ImageService();
    //     $key = base64_decode(base64_decode($this->imageId));
    //     $imageContent = $imageService->getImageContent($key);
    //     $this->image = base64_encode($imageContent);

    //     return view('livewire.photography.image-frame');   
    // }
    // code by chromedia

    // code by IT
    public function render()
    {
        // Image content fetching has been removed from Livewire to prevent base64
        // from leaking into the DOM. Image is now securely fetched via AJAX.
        return view('livewire.photography.image-frame', [
            'image' => 'READY',
            'category' => $this->category,
        ]);
    }
    // code by IT
}
