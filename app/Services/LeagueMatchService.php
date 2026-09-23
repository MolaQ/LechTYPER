<?php

namespace App\Services;

use App\Actions\CalculateMatchPointsAction;
use App\Models\LeagueRound;
use App\Models\MatchGame;
use App\Models\MatchSelection;
use App\Models\MatchSelectionAnswer;
use App\Models\SeasonLeague;
use App\Models\SeasonTeam;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeagueMatchService
{
    /**
     * @param  array<int, bool|null>  $correctAnswers  keyed by league_round_bonus_question_id
     */
    public function completeRound(LeagueRound $round, int $realScoreHome, int $realScoreAway, array $correctAnswers): void
    {
        DB::transaction(function () use ($round, $realScoreHome, $realScoreAway, $correctAnswers): void {
            $round->update(['real_score_home' => $realScoreHome, 'real_score_away' => $realScoreAway]);

            foreach ($round->bonusQuestions as $question) {
                $question->update(['correct_answer' => $correctAnswers[$question->id] ?? null]);
            }

            $round->loadMissing('matches', 'seasonLeague.league');
            $seasonLeagueId = (int) $round->season_league_id;
            $offensiveQuestions = $round->bonusQuestions->where('type', 'offensive')->values();
            $defensiveQuestions = $round->bonusQuestions->where('type', 'defensive')->values();

            foreach ($round->matches as $match) {
                $this->completeMatch($match, $seasonLeagueId, $realScoreHome, $realScoreAway, $offensiveQuestions, $defensiveQuestions);
            }

            $this->rebuildStandings($round->seasonLeague);

            if ($round->seasonLeague->league->level === 11) {
                app(SwissLeagueService::class)->generateNextRound($round->seasonLeague);
            }
        });
    }

    private function completeMatch(MatchGame $match, int $seasonLeagueId, int $realScoreHome, int $realScoreAway, Collection $offensiveQuestions, Collection $defensiveQuestions): void
    {
        $home = $this->scorePrediction($match, $match->home_team_id, $realScoreHome, $realScoreAway, $offensiveQuestions, $defensiveQuestions);
        $away = $this->scorePrediction($match, $match->away_team_id, $realScoreHome, $realScoreAway, $offensiveQuestions, $defensiveQuestions);

        $action = app(CalculateMatchPointsAction::class);
        $homeTotal = $action->applyDefensivePenalty($home['selection']->total_points, $away['defensive']);
        $awayTotal = $action->applyDefensivePenalty($away['selection']->total_points, $home['defensive']);

        $home['selection']->update(['points_defensive_applied' => $away['defensive'], 'total_points' => $homeTotal]);
        $away['selection']->update(['points_defensive_applied' => $home['defensive'], 'total_points' => $awayTotal]);

        $match->update(['status' => 'completed', 'home_score' => $homeTotal, 'away_score' => $awayTotal]);
        $this->updateStandings($match, $seasonLeagueId, $homeTotal, $awayTotal);
    }

    /**
     * @return array{selection: MatchSelection, defensive: int}
     */
    private function scorePrediction(MatchGame $match, int $teamId, int $realScoreHome, int $realScoreAway, Collection $offensiveQuestions, Collection $defensiveQuestions): array
    {
        $selection = $match->selections()->where('team_id', $teamId)->first();
        $team = Team::query()->with('user')->findOrFail($teamId);

        if ($selection === null && $this->isBot($team)) {
            $selection = $this->createBotPrediction($match, $team, $offensiveQuestions, $defensiveQuestions);
        }

        if ($selection === null) {
            $selection = MatchSelection::create(['match_id' => $match->id, 'team_id' => $teamId, 'submitted_at' => now()]);
        }

        if ($this->isBot($team)) {
            $selection->answers()->delete();
        }

        $answers = MatchSelectionAnswer::query()
            ->where('match_selection_id', $selection->id)
            ->pluck('answer', 'league_round_bonus_question_id')
            ->map(fn (?bool $answer): ?bool => $answer === null ? null : (bool) $answer);

        $points = app(CalculateMatchPointsAction::class)->calculate(
            $selection->home_score,
            $selection->away_score,
            $realScoreHome,
            $realScoreAway,
            $offensiveQuestions,
            $defensiveQuestions,
            $answers,
        );

        $selection->update(['points_base' => $points['base'], 'points_offensive' => $points['offensive'], 'points_defensive' => $points['defensive'], 'total_points' => $points['total']]);

        return ['selection' => $selection, 'defensive' => $points['defensive']];
    }

    private function createBotPrediction(MatchGame $match, Team $team, Collection $offensiveQuestions, Collection $defensiveQuestions): MatchSelection
    {
        $selection = MatchSelection::create([
            'match_id' => $match->id,
            'team_id' => $team->id,
            'home_score' => random_int(0, 3),
            'away_score' => random_int(0, 3),
            'submitted_at' => now(),
        ]);

        return $selection;
    }

    private function isBot(Team $team): bool
    {
        return str_starts_with(strtolower((string) $team->user?->email), 'bot.');
    }

    private function updateStandings(MatchGame $match, int $seasonLeagueId, int $homeScore, int $awayScore): void
    {
        $home = $homeScore <=> $awayScore;
        $homePoints = $home > 0 ? config('league.points.win') : ($home === 0 ? config('league.points.draw') : config('league.points.loss'));
        $awayPoints = $home < 0 ? config('league.points.win') : ($home === 0 ? config('league.points.draw') : config('league.points.loss'));

        $this->updateTeamStanding($seasonLeagueId, $match->home_team_id, $homePoints, $homeScore, $awayScore, $home);
        $this->updateTeamStanding($seasonLeagueId, $match->away_team_id, $awayPoints, $awayScore, $homeScore, -$home);
    }

    /**
     * Bots never answer bonus questions; this repairs completed matches whose bot
     * selections still carry stray answers saved before that rule was enforced.
     */
    public function repairBotBonusPoints(): int
    {
        $seasonLeagueIds = collect();

        MatchSelection::query()
            ->whereHas('answers')
            ->whereHas('match', fn ($query) => $query->where('status', 'completed'))
            ->with(['match', 'team.user'])
            ->get()
            ->each(function (MatchSelection $selection) use (&$seasonLeagueIds): void {
                if (! $this->isBot($selection->team)) {
                    return;
                }

                $selection->answers()->delete();
                $selection->update(['points_offensive' => 0, 'points_defensive' => 0]);
                $seasonLeagueIds->push($selection->match->season_league_id);
            });

        $seasonLeagueIds = $seasonLeagueIds->unique()->values();

        foreach ($seasonLeagueIds as $seasonLeagueId) {
            $this->recalculateCompletedMatches((int) $seasonLeagueId);
            $this->rebuildStandings(SeasonLeague::findOrFail($seasonLeagueId));
        }

        return $seasonLeagueIds->count();
    }

    private function recalculateCompletedMatches(int $seasonLeagueId): void
    {
        $action = app(CalculateMatchPointsAction::class);

        MatchGame::query()
            ->where('season_league_id', $seasonLeagueId)
            ->where('status', 'completed')
            ->with('selections')
            ->get()
            ->each(function (MatchGame $match) use ($action): void {
                $home = $match->selections->firstWhere('team_id', $match->home_team_id);
                $away = $match->selections->firstWhere('team_id', $match->away_team_id);

                if ($home === null || $away === null) {
                    return;
                }

                $homeTotal = $action->applyDefensivePenalty($home->points_base + $home->points_offensive, $away->points_defensive);
                $awayTotal = $action->applyDefensivePenalty($away->points_base + $away->points_offensive, $home->points_defensive);

                $home->update(['points_defensive_applied' => $away->points_defensive, 'total_points' => $homeTotal]);
                $away->update(['points_defensive_applied' => $home->points_defensive, 'total_points' => $awayTotal]);
                $match->update(['home_score' => $homeTotal, 'away_score' => $awayTotal]);
            });
    }

    private function rebuildStandings(SeasonLeague $seasonLeague): void
    {
        $seasonLeague->teams()->update([
            'played' => 0,
            'wins' => 0,
            'draws' => 0,
            'losses' => 0,
            'points' => 0,
            'bonus_points' => 0,
            'score_for' => 0,
            'score_against' => 0,
        ]);

        $seasonLeague->matches()
            ->where('status', 'completed')
            ->get()
            ->each(fn (MatchGame $match) => $this->updateStandings($match, (int) $seasonLeague->id, (int) $match->home_score, (int) $match->away_score));

        foreach ($seasonLeague->teams as $seasonTeam) {
            $seasonTeam->update([
                'bonus_points' => (int) MatchSelection::query()
                    ->where('team_id', $seasonTeam->team_id)
                    ->whereHas('match', fn ($query) => $query->where('season_league_id', $seasonLeague->id)->where('status', 'completed'))
                    ->sum(DB::raw('points_offensive + points_defensive')),
            ]);
        }
    }

    private function updateTeamStanding(int $seasonLeagueId, int $teamId, int $points, int $scoreFor, int $scoreAgainst, int $result): void
    {
        $standing = SeasonTeam::where('season_league_id', $seasonLeagueId)->where('team_id', $teamId)->firstOrFail();
        $standing->increment('played');
        $standing->increment('points', $points);
        $standing->increment('score_for', $scoreFor);
        $standing->increment('score_against', $scoreAgainst);
        $standing->increment($result > 0 ? 'wins' : ($result < 0 ? 'losses' : 'draws'));
    }
}
