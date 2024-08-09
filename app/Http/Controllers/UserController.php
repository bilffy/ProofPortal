<?php

namespace App\Http\Controllers;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $usersQuery = User::query();
        // TODO: Add initial filter for list of users only visible to this user's permission level
        $this->applySearch($usersQuery, $request->input('search', ''));
        $this->applySort($usersQuery, $request->input('sort', ''));
        return Inertia::render('Users/ManageUsers', [
            'results' => UserResource::collection($usersQuery->paginate($perPage)->withQueryString())
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Create', []);
    }

    protected function applySearch($query, $search)
    {
        return $query->when($search, function ($q, $searchString) {
            $q->where('email', 'like', "%{$searchString}%")
                ->orWhere('firstname', 'like', "%{$searchString}%")
                ->orWhere('lastname', 'like', "%{$searchString}%")
                // ->orWhere('name', 'like', "%{$searchString}%")
            ;
        });
    }

    protected function applyFilter($query, $filters)
    {
        // foreach($filters as $filter => $values) {
        // }
        return $query;
    }
    
    protected function applySort($query, $value)
    {
        if (!empty($value)) {
            $order = 'asc';
            if (str_starts_with($value, '-')) {
                $value = substr($value, 1);
                $order = 'desc';
            }
            $query->orderBy($value, $order);
        }
        return $query;        
    }
}
