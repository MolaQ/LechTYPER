<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $team = Team::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['name' => $request->user()->name],
        );

        return view('profile', compact('team'));
    }

    public function updateTeamName(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'team_name' => ['required', 'string', 'max:120'],
        ]);

        $team = Team::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['name' => $request->user()->name],
        );
        $team->update(['name' => $data['team_name']]);

        return back()->with('status', 'Nazwa drużyny została zaktualizowana.');
    }
}
