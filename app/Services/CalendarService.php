<?php


namespace App\Services;


use App\Models\Season;
use App\Models\SeasonCalendar;
use App\Models\Team;

class CalendarService
{

    public function startSeason()
    {
        $seasonId = $this->createNewSeason();
        return $this->createCalendar($seasonId);
    }
    /**
     * @return mixed
     */
    private function createNewSeason(): ?int
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
}
