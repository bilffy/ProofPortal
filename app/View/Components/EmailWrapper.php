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
    public $franchiseWebsite;

    /**
     * Create a new component instance.
     */
    public function __construct($franchiseName = '', $franchisePhone = '', $franchiseEmail = '', $franchiseWebsite = '')
    {
        $this->franchiseName = $franchiseName;
        $this->franchisePhone = $franchisePhone;
        $this->franchiseEmail = $franchiseEmail;
        $this->franchiseWebsite = $franchiseWebsite ?: config('app.franchise_web_address', 'www.msp.com.au');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('emails.partials.wrapper');
    }
}
