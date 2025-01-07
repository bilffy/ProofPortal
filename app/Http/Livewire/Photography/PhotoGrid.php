<?php

namespace App\Http\Livewire\Photography;

use App\Helpers\PhotographyHelper;
use App\Services\ImageService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class PhotoGrid extends Component
{
    use WithPagination;
    public $category;
    public $season;
    public $schoolKey;
    public $page = 1;
    
    public $images = [];
    public $selectedImages = [];
    public $search = '';
    public $filters = [];

    protected $listeners = [
        PhotographyHelper::EV_UPDATE_FILTER => 'updateFilters',
        PhotographyHelper::EV_UPDATE_SEARCH => 'updateSearch',
        PhotographyHelper::EV_SELECT_IMAGE => 'updateSelectedList',
        PhotographyHelper::EV_CLEAR_SELECTED_IMAGES => 'clearSelectedImages',
        PhotographyHelper::EV_CHANGE_TAB => 'clearSelectedImages',
    ];

    public function mount($category = 'portaits', $season = 1, $schoolKey = '', $filters = [])
    {
        $this->category = $category;
        $this->season = $season;
        $this->schoolKey = $schoolKey;
        $this->filters = $filters;

        $this->getImages();
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

    public function performSearch($term)
    {
        dd($term);
        $this->dispatch(PhotographyHelper::EV_CLEAR_SELECTED_IMAGES);
        $this->search = $term;
        $this->getImages();
    }

    public function updateFilters($year, $view, $class)
    {
        $this->dispatch(PhotographyHelper::EV_CLEAR_SELECTED_IMAGES);
        $this->filters = [
            'year' => $year,
            'view' => $view,
            'class' => $class
        ];
        $this->getImages();
    }

    private function getImages()
    {
        $imageService = new ImageService();
        $class = $this->filters['class'] ?? 'ALL';
        $options = [
            'tsSeasonId' => $this->season,
            'schoolKey' => $this->schoolKey,
            'folderKey' => $class,
            'search' => $this->search,
        ];
        $this->images = $imageService->getImagesAsBase64($options);
    }

    public function render()
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 30;
        $items = $this->images->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $paginator = new LengthAwarePaginator(
            $items,
            $this->images->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
        return view('livewire.photography.photo-grid', ['paginatedImages' => $paginator]);
    }
}
