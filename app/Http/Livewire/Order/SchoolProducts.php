<?php

namespace App\Http\Livewire\Order;

/**
 * The "School Products" page (/school-products): the same order form as
 * /order, but listing only the products flagged is_school_product.
 */
class SchoolProducts extends Order
{
    protected bool $schoolProducts = true;

    protected string $pageTitle = 'School Products';
}
