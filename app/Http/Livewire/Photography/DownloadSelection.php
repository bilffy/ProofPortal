<?php

namespace App\Http\Livewire\Photography;

use App\Helpers\PhotographyHelper;
use Livewire\Component;

class DownloadSelection extends Component
{

    public $selectedImages = [];

    protected $listeners = [
        PhotographyHelper::EV_SELECT_IMAGE => 'updateSelectedList',
        PhotographyHelper::EV_CHANGE_TAB => 'clearSelectedImages',
        PhotographyHelper::EV_CLEAR_SELECTED_IMAGES => 'clearSelectedImages',
    ];

    public function updateSelectedList($imageKey)
    {
        if (in_array($imageKey, $this->selectedImages)) {
            $this->selectedImages = array_filter($this->selectedImages, fn ($value) => $value != $imageKey);
        } else {
            $this->selectedImages[] = $imageKey;
        }
    }

    public function clearSelectedImages()
    {
        $this->selectedImages = [];
    }

    public function downloadImages()
    {
        // Attach download logic here
        dd("Feature coming soon.");
    }
    
    public function render()
    {
        return view('livewire.photography.download-selection');
    }
}
