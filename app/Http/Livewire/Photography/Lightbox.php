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
    public $category = PhotographyHelper::TAB_PORTRAITS;
    public $externalSubjectId = "";

    protected $listeners = [
        PhotographyHelper::EV_SELECT_IMAGE => 'updateSubject',
    ];

    public function mount($schoolKey = "")
    {
        $this->schoolKey = $schoolKey;
    }

    public function updateSubject($subject, $category, $externalSubjectId)
    {
        $this->subject = $subject;
        $this->category = $category;
        $this->externalSubjectId = $externalSubjectId;
    }

    private function getImages()
    {
        $imageService = new ImageService();
        
        switch ($this->category) {
            case PhotographyHelper::TAB_GROUPS:
            case PhotographyHelper::TAB_OTHERS:
                $g = explode('-', $this->subject);
                $images = $imageService->getGroupImages($this->schoolKey, trim($g[0]));
                break;
            case PhotographyHelper::TAB_PORTRAITS:
            default:
                $images = $imageService->getSubjectImages($this->schoolKey, '', '',$this->externalSubjectId);
        }
        
        $imageCount = $images->count();
        $noImageCount = 0;
        foreach ($images as $image) {
            if (property_exists($image, 'ts_subjectkey') && !$imageService->getIsImageFound($image->ts_subjectkey)) {
                $noImageCount++;
            } else if (property_exists($image, 'ts_folderkey') && !$imageService->getIsImageFound($image->ts_folderkey)) {
                $noImageCount++;
            }
        }

        $this->dispatch(PhotographyHelper::EV_TOGGLE_NO_IMAGES, [
            'category' => 'LIGHTBOX',
            'hasImages' => $imageCount > 0 && $imageCount != $noImageCount,
        ]);
        
        $list = $imageService->getImagesAsBase64($images, $this->category); 
        
        return $list;
    }

    public function render()
    {
        $this->images = empty($this->subject) ? [] : $this->getImages();
        return view('livewire.photography.lightbox', ['images' => $this->images]);
    }
}
