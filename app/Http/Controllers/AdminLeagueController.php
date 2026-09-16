<?php

namespace App\Http\Controllers;

use App\Models\MatchGame;
use App\Services\LeagueMatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminLeagueController extends Controller
{
    public function completeMatch(Request $request, MatchGame $match, LeagueMatchService $service): RedirectResponse
    {
        $data = $request->validate([
            'stats' => ['array'],
            'stats.*.player_id' => ['required', 'integer', 'exists:players,id'],
            'stats.*.points' => ['required', 'numeric'],
        ]);
        $service->complete($match, $data['stats'] ?? []);

        return back()->with('status', 'Mecz został rozliczony, a tabela zaktualizowana.');
    }
}
