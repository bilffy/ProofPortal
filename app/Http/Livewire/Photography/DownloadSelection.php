<?php

namespace App\Http\Livewire\Photography;

use App\Helpers\PhotographyHelper;
use App\Models\DownloadDetail;
use App\Models\DownloadRequested;
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

    /**
     * Request download details
     * This method will create a new download request and store the details
     * 
     * @param array $details
     * @return DownloadRequested
     */
    public function requestDownloadDetails(array $details)
    {
        $downloadRequest = DownloadRequested::create([
            'requested_by' => auth()->id(),
            'requested_date' => now(),
        ]);

        foreach ($details as $detail) {
            DownloadDetail::create([
                'download_id' => $downloadRequest->id,
                'ts_jobkey' => $detail['ts_jobkey'],
                'keyorigin' => $detail['keyorigin'],
                'keyvalue' => $detail['keyvalue'],
            ]);
        }

        return $downloadRequest;
    }
    
    public function render()
    {
        return view('livewire.photography.download-selection');
    }
}
