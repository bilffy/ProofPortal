<?php

namespace App\Http\Livewire\Settings;

use App\Helpers\FilenameFormatHelper;
use Auth;
use Livewire\Component;

class FilenameFormat extends Component
{
    public $fileFormats = [];
    public $fieldOptions = [];
    public $imageTypes = [];
    public $visibilityOptions = [];

    public $listeners = [
        'EV_ADD_FILENAME_FORMAT' => 'addNewFormat',
    ];

    public function mount()
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return redirect()->route('dashboard');
        }
        $this->fileFormats = \App\Models\FilenameFormat::query()
            ->orderBy('format_key', 'desc')
            ->get(['name', 'format', 'format_key', 'visibility'])
            ->map(function ($format) {
                return [
                    'name' => $format->name,
                    'format' => $format->format,
                    'format_key' => $format->format_key,
                    'visibility' => $format->visibility,
                ];
            })->toArray();
        $this->visibilityOptions = \App\Models\FilenameFormat::query()
            ->get(['visibility_options'])
            ->pluck('visibility_options')
            ->first() ?? [];
        foreach ($this->visibilityOptions as $option) {
            $this->imageTypes[$option] = ucfirst($option);
        }
        $this->fieldOptions = FilenameFormatHelper::getFormatOptionsList();
    }

    public function addNewFormat($type, $pattern, $name)
    {
        // Check if user is admin
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return;
        }

        $newFormat = \App\Models\FilenameFormat::create([
            'name' => $name,
            'format' => $pattern,
            'format_key' => 0, // This will be updated later
            'visibility' => [$type],
            'visibility_options' => $this->visibilityOptions,
        ]);

        $newFormat->update([
            'format_key' => $newFormat->id,
        ]);

        array_unshift($this->fileFormats, [
            'name' => $newFormat->name,
            'format' => $newFormat->format,
            'format_key' => $newFormat->format_key,
            'visibility' => $newFormat->visibility,
        ]);

        $this->dispatch('EV_FILENAME_FORMAT_ADDED', []);
    }
    
    public function render()
    {   
        return view('livewire.settings.filename-format',
            [
                'formats' => $this->fileFormats,
                'imageTypes' => $this->imageTypes,
                'fieldOptions' => $this->fieldOptions,
            ]);
    }
}
