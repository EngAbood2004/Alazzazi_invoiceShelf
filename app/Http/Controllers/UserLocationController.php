<?php

namespace App\Http\Controllers;

use App\Models\UserLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserLocationController extends Controller
{
    public function index(): View
    {
        $locationsQuery = UserLocation::with('user:id,name,email')->latest();
        if (! auth()->user()->isAdmin()) {
            $locationsQuery->where('user_id', auth()->id());
        }
        $locations = $locationsQuery->paginate(15);
        $myLocation = UserLocation::where('user_id', auth()->id())->latest()->first();
        return view('locations.index', compact('locations', 'myLocation'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'label' => ['nullable', 'string', 'max:120'],
        ]);
        UserLocation::create(['user_id' => auth()->id(), ...$data]);
        return redirect()->route('locations.index')->with('success', __('Location saved successfully.'));
    }
}
