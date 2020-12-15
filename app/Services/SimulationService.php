<?php


namespace App\Services;


use App\Models\Season;
use App\Models\SeasonCalendar;
use App\Models\SeasonStatistic;
use App\Models\Team;

class SimulationService
{
    public function startSeason()
    {
        $seasonId = $this->createNewSeason();
        return $this->createCalendar($seasonId);
    }

    public function nextStage(int $season)
    {
        if ($nextStage = $this->getLastPlayedStage($season)) {
            $this->playStage($season, $nextStage);
        }

        return SeasonStatistic::where('season_id', $season)->get();
    }

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

    /**
     * @return mixed
     */
    private function createNewSeason()
    {
        return Season::create()->id;
    }

    /**
     * @param int $seasonId
     * @return \Illuminate\Support\Collection
     */
    private function createCalendar(int $seasonId)
    {
        $teams = $this->getTeams();
        $matches = array();

        $allTeams = count($teams);

        $firstHalf = range(1, 19, 1);
        $secondHalf = range(20, 38, 1);

        $this->firstStage($firstHalf, $secondHalf, $teams, $allTeams, $matches);
        $this->nextStages($firstHalf, $secondHalf, $allTeams, $teams, $matches);
        $matches = collect($matches)->sortBy('day')->toArray();
        return $this->saveCalendar($seasonId, $matches);
    }

    /**
     * @return array
     */
    private function getTeams()
    {
        $teams = array();
        $items = Team::get()->pluck('id');

        foreach ($items as $key => $item) {
            $teams[$key+1] = $item;
        }

        return $teams;
    }

    /**
     * @param array $firstHalf
     * @param array $secondHalf
     * @param array $teams
     * @param int $allTeams
     * @param array $matches
     */
    private function firstStage(array $firstHalf, array $secondHalf, array $teams, int $allTeams, array &$matches)
    {
        for ($i = 1; $i <= ($allTeams / 2); $i++) {
            $matches[] = [
                'day' => $firstHalf[0],
                'first' => $teams[$i],
                'second' => $teams[($allTeams - $i + 1)],
            ];

            $matches[] = [
                'day' => $secondHalf[0],
                'first' => $teams[($allTeams-$i+1)],
                'second' => $teams[$i],
            ];
        }
    }

    /**
     * @param array $firstHalf
     * @param $secondHalf
     * @param int $allTeams
     * @param array $teams
     * @param array $matches
     */
    private function nextStages(array $firstHalf, $secondHalf, int $allTeams, array $teams, array &$matches)
    {
        for ($i = 2; $i < $allTeams; $i++) {

            $team2 = $teams[2];

            for ($y = 2; $y < $allTeams; $y++) {
                $teams[$y] = $teams[$y+1];
            }
            $teams[$allTeams] = $team2;

            for ($j = 1;$j <= ($allTeams / 2); $j++) {
                $matches[] = [
                    'day' => $firstHalf[$i - 1],
                    'first' => $teams[$j],
                    'second' => $teams[($allTeams-$j+1)]
                ];
                $matches[] = [
                    'day' => $secondHalf[$i - 1],
                    'first' => $teams[($allTeams-$j+1)],
                    'second' => $teams[$j],
                ];
            }
        }
    }

    /**
     * @param int $season
     * @param array $matches
     * @return \Illuminate\Support\Collection
     */
    private function saveCalendar(int $season, array $matches)
    {
        $result = collect();
        foreach ($matches as $key => $value) {
            $result->push(SeasonCalendar::create([
                'season_id' => $season,
                'first_team_id' => $value['first'],
                'second_team_id' => $value['second'],
                'stage' => $value['day'],
            ]));
        }
        return $result;
    }

    private function getLastPlayedStage(int $season)
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
