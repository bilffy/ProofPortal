<?php

namespace App\Http\Livewire\Photography;

use App\Helpers\PhotographyHelper;
use Livewire\Component;

class PhotoGrid extends Component
{
    public $images = [];
    public $selectedImages = [];
    public $category;

    protected $listeners = [
        PhotographyHelper::EV_UPDATE_FILTER => 'updateFilter',
        PhotographyHelper::EV_SELECT_IMAGE => 'updateSelectedList',
        PhotographyHelper::EV_CLEAR_SELECTED_IMAGES => 'clearSelectedImages',
        PhotographyHelper::EV_CHANGE_TAB => 'clearSelectedImages',
    ];

    public function mount($category = 'portaits')
    {
        $this->category = $category;
    }

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

    public function updateFilter()
    {

    }

    public function render()
    {
        return view('livewire.photography.photo-grid');
    }
}
