<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EmailWrapper extends Component
{
    public $franchiseName;
    public $franchisePhone;
    public $franchiseEmail;

    /**
     * Create a new component instance.
     */
    public function __construct($franchiseName = '', $franchisePhone = '', $franchiseEmail = '')
    {
        $this->franchiseName = $franchiseName;
        $this->franchisePhone = $franchisePhone;
        $this->franchiseEmail = $franchiseEmail;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('emails.partials.wrapper');
    }
}
