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
        PhotographyHelper::EV_UPDATE_SEARCH => 'performSearch',
        PhotographyHelper::EV_SELECT_IMAGE => 'updateSelectedList',
        PhotographyHelper::EV_CLEAR_SELECTED_IMAGES => 'clearSelectedImages',
        PhotographyHelper::EV_CHANGE_TAB => 'clearSelectedImages',
    ];

    public function mount($category = 'portaits', $season = 1, $schoolKey = '')
    {
        $this->category = $category;
        $this->season = $season;
        $this->schoolKey = $schoolKey;

        $this->setupFilters($season);
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
        $this->resetPage();
    }

    public function performSearch($term)
    {
        $this->search = $term;
        // $this->dispatch(PhotographyHelper::EV_CLEAR_SELECTED_IMAGES);
        $this->clearSelectedImages();
        $this->getImages();
    }

    /**
     * Setup photography page filters
     * @param int $year
     * @param string $view
     * @param array $class
     * @return void
     */
    private function setupFilters(int $year, string $view = 'ALL', array $class = [])
    {
        $initialState = empty($this->filters);
        $this->season = $year;
        $viewOptions['ALL'] = 'All';
        $classOptions = [];
        $imageService = new ImageService();
        $resetViewAndClass = $resetClass = false;
        
        // If year changed, update view options
        if ($initialState || $year != $this->filters['year']) {
            $this->filters['year'] = $year;
            $options = $imageService->getFolderForView(
                $year, 
                $this->schoolKey,
                '!=',
                'SP'
            )->values()->toArray();

            // reset view and class when year value changes
            $resetViewAndClass = true;
            foreach ($options as $option) {
                $viewOptions[$option->external_name] = $option->external_name;
            }
            $this->dispatch(PhotographyHelper::EV_UPDATE_FILTER_DATA, 'view', $viewOptions);
        }
        
        // If view changed, update classes options
        if ($initialState || $resetViewAndClass || $view != $this->filters['view']) {
            $options = $imageService->getFoldersByTag(
                $year, 
                $this->schoolKey,
                $view,
                $this->getVisibility()
            )->toArray();

            // reset class when view value changes
            $resetClass = key_exists('view', $this->filters) && $view != $this->filters['view'];
            $allClasses = [];
            foreach ($options as $option) {
                $classOptions[$option->ts_folderkey] = $option->ts_foldername;
                $allClasses[] = $option->ts_folderkey;
            }
            $this->filters['allClasses'] = $allClasses;
            $this->dispatch(PhotographyHelper::EV_UPDATE_FILTER_DATA, 'class', $classOptions);
        }

        $this->filters['view'] = $resetViewAndClass ? 'ALL' : $view;
        $this->filters['class'] = $resetClass || $resetViewAndClass ? [] : $class;
    }

    public function updateFilters($year, $view, $class)
    {
        $this->setupFilters($year, $view, $class);
        $this->getImages();
        // $this->dispatch(PhotographyHelper::EV_CLEAR_SELECTED_IMAGES);
        $this->clearSelectedImages();
    }

    private function getImages()
    {
        $imageService = new ImageService();
        $keys = empty($this->filters['class']) ? $this->filters['allClasses'] : $this->filters['class'];
        $options = [
            'tsSeasonId' => $this->season,
            'schoolKey' => $this->schoolKey,
            'folderKeys' => $keys,
            'searchTerm' => $this->search,
        ];
        $this->images = $imageService->getImagesAsBase64($options);
    }

    private function getVisibility()
    {
        switch($this->category) {
            case PhotographyHelper::TAB_PORTRAITS:
                return 'is_visible_for_portrait';
            case PhotographyHelper::TAB_GROUPS:
                return 'is_visible_for_group';
            case PhotographyHelper::TAB_OTHERS:
                return 'is_visible_for_portrait';
        }
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
