<?php

namespace App\Services;

use App\Models\MatchGame;
use App\Models\MatchSelection;
use App\Models\PlayerMatchStat;
use App\Models\SeasonTeam;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class LeagueMatchService
{
    /**
     * @param  array<int, array{player_id: int, points: numeric}>  $stats
     */
    public function complete(MatchGame $match, array $stats): void
    {
        DB::transaction(function () use ($match, $stats): void {
            abort_unless($match->status === 'scheduled', 422, 'Mecz został już rozliczony.');

            foreach ($stats as $stat) {
                PlayerMatchStat::updateOrCreate(
                    ['match_id' => $match->id, 'player_id' => $stat['player_id']],
                    ['points' => $stat['points']],
                );
            }

            $homeScore = $this->scoreSelection($match, $match->home_team_id);
            $awayScore = $this->scoreSelection($match, $match->away_team_id);
            $match->update(['status' => 'completed', 'home_score' => $homeScore, 'away_score' => $awayScore]);
            $this->updateStandings($match, $homeScore, $awayScore);

            $match->loadMissing('seasonLeague.league');
            if ($match->seasonLeague->league->level === 11) {
                app(SwissLeagueService::class)->generateNextRound($match->seasonLeague);
            }
        });
    }

    public function penalizeMissingSelection(MatchGame $match, int $teamId): void
    {
        if (MatchSelection::where('match_id', $match->id)->where('team_id', $teamId)->exists()) {
            return;
        }

        $player = Team::findOrFail($teamId)->players()->where(function ($query): void {
            $query->whereNull('injury_until')->orWhere('injury_until', '<=', now());
        })->inRandomOrder()->first();

        $player?->update(['injury_until' => $match->scheduled_at->copy()->addDays(config('league.missed_selection_injury_days', 14))]);
    }

    private function scoreSelection(MatchGame $match, int $teamId): float
    {
        $selection = $match->selections()->where('team_id', $teamId)->with('players')->first();
        if ($selection === null) {
            $this->penalizeMissingSelection($match, $teamId);

            return 0;
        }

        return (float) PlayerMatchStat::query()
            ->where('match_id', $match->id)
            ->whereIn('player_id', $selection->players->pluck('player_id'))
            ->sum('points');
    }

    private function updateStandings(MatchGame $match, float $homeScore, float $awayScore): void
    {
        $home = $homeScore <=> $awayScore;
        $homePoints = $home > 0 ? config('league.points.win') : ($home === 0 ? config('league.points.draw') : config('league.points.loss'));
        $awayPoints = $home < 0 ? config('league.points.win') : ($home === 0 ? config('league.points.draw') : config('league.points.loss'));

        $this->updateTeamStanding($match->seasonLeague_id, $match->home_team_id, $homePoints, $homeScore, $awayScore, $home);
        $this->updateTeamStanding($match->seasonLeague_id, $match->away_team_id, $awayPoints, $awayScore, $homeScore, -$home);
    }

    private function updateTeamStanding(int $seasonLeagueId, int $teamId, int $points, float $scoreFor, float $scoreAgainst, int $result): void
    {
        $standing = SeasonTeam::where('season_league_id', $seasonLeagueId)->where('team_id', $teamId)->firstOrFail();
        $standing->increment('played');
        $standing->increment('points', $points);
        $standing->increment('score_for', (int) $scoreFor);
        $standing->increment('score_against', (int) $scoreAgainst);
        $standing->increment($result > 0 ? 'wins' : ($result < 0 ? 'losses' : 'draws'));
    }
}
