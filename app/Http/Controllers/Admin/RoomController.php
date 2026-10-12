<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\RoomRequest;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Rooms/Index', [
            'rooms' => Room::withCount('timetables')->orderBy('building')->orderBy('name')->get(),
        ]);
    }

    public function store(RoomRequest $request): RedirectResponse
    {
        Room::create($request->validated());

        return back()->with('success', 'Salle créée.');
    }

    public function update(RoomRequest $request, Room $room): RedirectResponse
    {
        $room->update($request->validated());

        return back()->with('success', 'Salle mise à jour.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        // Les créneaux d'emploi du temps rattachés passent en nullOnDelete.
        $room->delete();

        return back()->with('success', 'Salle supprimée.');
    }
}