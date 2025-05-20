<?php

namespace App\Http\Livewire\Photography;

use App\Helpers\PhotographyHelper;
use App\Services\ImageService;
use Livewire\Component;

class Lightbox extends Component
{
    public $images = [];
    public $subject = "";
    public $schoolKey = "";

    protected $listeners = [
        PhotographyHelper::EV_SELECT_IMAGE => 'updateSubject',
    ];

    public function mount($schoolKey = "")
    {
        $this->schoolKey = $schoolKey;
    }

    public function updateSubject($subject)
    {
        // dd($subject);
        $this->subject = $subject;
    }

    private function getImages()
    {
        $imageService = new ImageService();
        $s = explode(' ', $this->subject);
        $subjectImages = $imageService->getSubjectImages($this->schoolKey, $s[0], $s[1]);
        // $imageCount = $subjectImages->count();
        
        // dd($subjectImages);
        return $imageService->getImagesAsBase64($subjectImages);
    }

    public function render()
    {
        $this->images = empty($this->subject) ? [] : $this->getImages();
        return view('livewire.photography.lightbox', ['images' => $this->images]);
    }
}
