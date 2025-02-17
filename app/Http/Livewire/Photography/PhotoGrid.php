<?php

namespace App\Http\Livewire\Photography;

use App\Helpers\PhotographyHelper;
use App\Services\ImageService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class PhotoGrid extends Component
{
    use WithPagination, WithoutUrlPagination;
    public $category;
    public $season;
    public $schoolKey;
    public $page = 1;
    
    public $images = [];
    public $search = '';
    public $filters = [];

    public $viewOptions = ['ALL' => 'All'];

    protected $listeners = [
        PhotographyHelper::EV_UPDATE_FILTER => 'updateFilters',
        PhotographyHelper::EV_UPDATE_SEARCH => 'performSearch',
    ];

    public function mount($category = 'portaits', $season = 1, $schoolKey = '')
    {
        $this->category = $category;
        $this->season = $season;
        $this->schoolKey = $schoolKey;

        $this->setupFilters($season);
    }

    // public function rendered($view, $html)
    // {
    //     $this->dispatch('photo-grid-updated', ['category' => $this->category]);
    // }

    public function performSearch($term, $category)
    {
        if ($category != $this->category) {
            return;
        }
        $this->search = $term;
        $this->resetPage();
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
        $classOptions = [];
        $imageService = new ImageService();
        $resetViewAndClass = $resetClass = false;
        
        // If year changed, update view options
        if ($initialState || $year != $this->filters['year']) {
            $this->filters['year'] = $year;
            $options = $imageService->getFolderForView2(
                $year, 
                $this->schoolKey,
                $this->category
            )->values()->toArray();

            // reset view and class when year value changes
            $resetViewAndClass = true;
            $viewOptions['ALL'] = 'All';
            foreach ($options as $option) {
                $viewOptions[$option->external_name] = $option->external_name;
            }
            $this->viewOptions = $viewOptions;
            $this->dispatch(PhotographyHelper::EV_UPDATE_FILTER_DATA, $this->category, 'view', $viewOptions);
        }
        
        // If view changed, update classes options
        if ($initialState || $resetViewAndClass || $view != $this->filters['view']) {
            $views = 'ALL' == $view ? array_keys($this->viewOptions) : [$view];
            $options = $imageService->getFoldersByTag(
                $year, 
                $this->schoolKey,
                $views,
                $this->category
            )->toArray();

            // reset class when view value changes
            $resetClass = key_exists('view', $this->filters) && $view != $this->filters['view'];
            $allClasses = [];
            foreach ($options as $option) {
                $classOptions[$option->ts_folderkey] = $option->ts_foldername;
                $allClasses[] = $option->ts_folderkey;
            }
            $this->filters['allClasses'] = $allClasses;
            $this->dispatch(PhotographyHelper::EV_UPDATE_FILTER_DATA, $this->category, 'class', $classOptions);
        }

        $this->filters['view'] = $resetViewAndClass ? 'ALL' : $view;
        $this->filters['class'] = $resetClass || $resetViewAndClass ? [] : $class;
    }

    public function updateFilters($year, $view, $class, $category)
    {
        if ($category != $this->category) {
            return;
        }
        $this->setupFilters($year, $view, $class);
        $this->resetPage();
        
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
        $filteredImages = $imageService->getFilteredPhotographyImages($options, $this->category);
        
        $paginated = $imageService->paginate(
            $filteredImages,
            30,
            null,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
        
        // Modify the paginated items
        $modifiedItems = $imageService->getImagesAsBase64($paginated->getCollection(), $this->category);
        $paginated->setCollection($modifiedItems);
        
        return $paginated;
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

    private function getOperator()
    {
        switch($this->category) {
            case PhotographyHelper::TAB_PORTRAITS:
                return '!=';
            case PhotographyHelper::TAB_GROUPS:
                return '=';
            case PhotographyHelper::TAB_OTHERS:
                return '!=';
        }
    }

    public function render()
    {
        $paginatedImages = $this->getImages();
        return view('livewire.photography.photo-grid', ['paginatedImages' => $paginatedImages]);
    }
}
