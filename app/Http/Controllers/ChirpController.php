<?php

namespace App\Http\Controllers;

use App\Models\Chirp;  
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ChirpController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $chirps = Chirp::with('user')->latest()->take(50)->get();

        return view('home', ['chirps' => $chirps]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'message' => 'required|string|max:255',
            ],
            [
                'message.required' => 'Please write something to chirp!',
                'message.max' => 'Chirps must be 255 characters or less',
            ]
        );

        // Use the authenticated user
        auth()->user()->chirps()->create($validated);

        // Redirect back to the feed
        return redirect('/')->with('success', 'Chirp created!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Chirp $chirp)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Chirp $chirp)
    {
        $this->authorize('update', $chirp);

        return view('chirps.edit', compact('chirp'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Chirp $chirp)
    {
        $this->authorize('update', $chirp);

        $validated = $request->validate(
            [
                'message' => 'required|string|max:255',
            ],
            [
                'message.required' => 'Please write something to chirp!',
                'message.max' => 'Chirps must be 255 characters or less',
            ]
        );

        // Create the chirp (no user for now - we'll add auth later)
        $chirp->update($validated);

        // Redirect back to the feed
        return redirect('/')->with('success', 'Chirp updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Chirp $chirp)
    {
        $this->authorize('delete', $chirp);

        $chirp->delete();

        return redirect('/')->with('success', 'Chirp deleted!');
    }
}
