<?php

namespace App\Http\Livewire\Settings;

use App\Models\Season;
use Livewire\Component;

class ProofingSeasonEnable extends Component
{
    public $seasonStates = [];

    public function mount()
    {
        $this->seasonStates = Season::pluck('show_in_portal', 'id')
            ->map(fn($v) => (bool)$v)
            ->toArray();
    }

    public function toggleSeason($seasonId)
    {
        $season = Season::find($seasonId);
        if ($season) {
            $season->show_in_portal = !$season->show_in_portal;
            $season->save();
            $this->seasonStates[$seasonId] = (bool)$season->show_in_portal;
        }
    }

    public function render()
    {
        return view('livewire.settings.proofing-season-enable', [
            'seasons' => Season::orderBy('code', 'desc')->get(),
        ]);
    }
}
