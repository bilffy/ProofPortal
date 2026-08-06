<?php

namespace App\Http\Livewire\Settings;

use App\Models\Season;
use Livewire\Component;

class ProofingSeasonEnable extends Component
{
    public function toggleSeasonPortal(string $seasonId): void
    {
        $season = Season::find($seasonId);
        if (!$season) {
            return;
        }

        $season->show_in_portal = (int) $season->show_in_portal === 1 ? 0 : 1;
        $season->save();
    }

    public function toggleSeasonProofing(string $seasonId): void
    {
        $season = Season::find($seasonId);
        if (!$season) {
            return;
        }

        $season->show_in_proofing = (int) $season->show_in_proofing === 1 ? 0 : 1;
        $season->save();
    }

    public function render()
    {
        return view('livewire.settings.proofing-season-enable', [
            'seasons' => Season::query()->orderBy('code', 'desc')->get(),
        ]);
    }
}
