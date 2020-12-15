<?php


namespace App\Services;


class SimulationService
{
    private StageService $stageService;

    private CalendarService $calendarService;

    public function __construct(StageService $stageService, CalendarService $calendarService)
    {
        $this->stageService = $stageService;
        $this->calendarService = $calendarService;
    }

    public function startSeason()
    {
        return $this->calendarService->startSeason();
    }

    public function nextStage(int $season)
    {
        return $this->stageService->nextStage($season);
    }

    public function finishSeason(int $season)
    {
        return $this->stageService->finishSeason($season);
    }
}
