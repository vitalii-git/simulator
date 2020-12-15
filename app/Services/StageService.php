<?php


namespace App\Services;


use App\Models\SeasonCalendar;
use App\Models\SeasonStatistic;

class StageService
{
    public function finishSeason(int $season)
    {
        $maxStage = SeasonCalendar::where('season_id', $season)->max('stage');
        $latestStage = $this->getLastPlayedStage($season);

        for ($i = $latestStage; $i < $maxStage; $i++) {
            if ($nextStage = $this->getLastPlayedStage($season)) {
                $this->playStage($season, $nextStage);
            } else {
                break;
            }
        }

        return SeasonStatistic::where('season_id', $season)->get();
    }

    public function nextStage(int $season)
    {
        if ($nextStage = $this->getLastPlayedStage($season)) {
            $this->playStage($season, $nextStage);
        }

        return SeasonStatistic::where('season_id', $season)->get();
    }

    public function getLastPlayedStage(int $season)
    {
        $latestStage = SeasonStatistic::where('season_id', $season)->with('calendar')->orderBy('id', 'DESC')->first()->calendar->stage ?? 0;
        $maxStage = SeasonCalendar::where('season_id', $season)->max('stage');

        if ($latestStage < $maxStage) {
            return $latestStage ? $latestStage + 1 : 1;
        }
        return false;
    }

    private function playStage(int $season, int $nexStage = 1): void
    {
        $matches = $this->calendarByStage($season, $nexStage);
        foreach ($matches as $match) {
            $firstScored = $this->getAverageScored($match->first_team_id, $season);
            $firstMissed = $this->getAverageMissed($match->first_team_id, $season);

            $secondScored = $this->getAverageScored($match->second_team_id, $season);
            $secondMissed = $this->getAverageMissed($match->second_team_id, $season);

            $first = $this->poisson($this->getChance($firstScored, $firstMissed), $this->getChance($secondScored, $secondMissed));
            $second = $this->poisson($this->getChance($secondScored, $secondMissed), $this->getChance($firstScored, $firstMissed));

            $this->getScore($first, $second, $match);
        }
    }

    private function getScore($firstPercentage, $secondPercentage, $match)
    {
        if ($firstPercentage > $secondPercentage) {
            $firstScored = rand(1, 5);
            $secondScored = rand(0, $firstScored - 1);
            $this->saveMatchResult($match->season_id, $match->id, $match->first_team_id, $match->second_team_id, $firstScored, $secondScored);
        } else if ($firstPercentage < $secondPercentage) {
            $secondScored = rand(1, 5);
            $firstScored = rand(0, $secondScored - 1);
            $this->saveMatchResult($match->season_id, $match->id, $match->first_team_id, $match->second_team_id, $firstScored, $secondScored);
        } else {
            $firstScored = rand(1, 5);
            $this->saveMatchResult($match->season_id, $match->id, $match->first_team_id, $match->second_team_id, $firstScored, $firstScored);
        }
    }

    private function saveMatchResult(int $season, int $calendar, int $firstTeam, int $secondTeam, int $firstScore, int $secondScore)
    {
        SeasonStatistic::insert([
            [
                'season_id' => $season,
                'calendar_id' => $calendar,
                'team_id' => $firstTeam,
                'scored' => $firstScore,
                'missed' => $secondScore
            ],
            [
                'season_id' => $season,
                'calendar_id' => $calendar,
                'team_id' => $secondTeam,
                'scored' => $secondScore,
                'missed' => $firstScore
            ]
        ]);
    }

    private function getChance($scored, $missed)
    {
        return ($missed ? $scored / $missed : 0) * ($scored ? $missed / $scored : 0) * $scored;
    }

    private function poisson($chance, $occurrence)
    {
        return round((exp(-$chance) * pow($chance, $occurrence) / $this->factorial($occurrence)) * 100, 2);
    }

    private function factorial($number)
    {
        if ($number < 2) {
            return 1;
        } else {
            return ($number * $this->factorial($number - 1));
        }
    }

    public function getAverageScored(int $team, int $season)
    {
        return $this->getPlayedGames($team, $season) ? $this->getScored($team, $season) / $this->getPlayedGames($team, $season) : rand(1,5);
    }

    public function getAverageMissed(int $team, int $season)
    {
        return $this->getPlayedGames($team, $season) ? $this->getMissed($team, $season) / $this->getPlayedGames($team, $season) : rand(1,5);
    }

    private function getPlayedGames(int $team, int $season)
    {
        return SeasonStatistic::where('team_id', $team)->where('season_id', $season)->count();
    }

    private function getScored(int $team, int $season)
    {
        return SeasonStatistic::where('team_id', $team)->where('season_id', $season)->sum('scored');
    }

    private function getMissed(int $team, int $season)
    {
        return SeasonStatistic::where('team_id', $team)->where('season_id', $season)->sum('missed');
    }

    private function calendarByStage(int $season, int $stage)
    {
        return SeasonCalendar::where('season_id', $season)->where('stage', $stage)->get();
    }

}
