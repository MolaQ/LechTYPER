<?php

namespace App\Services;

use App\Actions\CalculateMatchPointsAction;
use App\Models\LeagueRound;
use App\Models\MatchGame;
use App\Models\MatchSelection;
use App\Models\MatchSelectionAnswer;
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
                if ($match->status !== 'scheduled') {
                    continue;
                }

                $this->completeMatch($match, $seasonLeagueId, $realScoreHome, $realScoreAway, $offensiveQuestions, $defensiveQuestions);
            }

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

        $selection->update(['points_base' => $points['base'], 'points_offensive' => $points['offensive'], 'total_points' => $points['total']]);

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

        foreach ($offensiveQuestions->concat($defensiveQuestions) as $question) {
            MatchSelectionAnswer::create([
                'match_selection_id' => $selection->id,
                'league_round_bonus_question_id' => $question->id,
                'answer' => (bool) random_int(0, 1),
            ]);
        }

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
