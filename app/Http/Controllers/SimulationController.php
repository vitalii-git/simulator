<?php

namespace App\Http\Controllers;

use App\Http\Resources\SeasonCalendarResource;
use App\Http\Resources\SeasonStatisticResource;
use App\Services\SimulationService;

/**
 * Class SimulationController
 * @package App\Http\Controllers
 */
class SimulationController extends Controller
{
    /**
     * @var SimulationService
     */
    private SimulationService $service;

    /**
     * SimulationController constructor.
     * @param SimulationService $service
     */
    public function __construct(SimulationService $service)
    {
        $this->service = $service;
    }

    public function startSeason()
    {
        return SeasonCalendarResource::collection($this->service->startSeason());
    }

    /**
     * @param int $season
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function nextStage(int $season)
    {;
        return SeasonStatisticResource::collection($this->service->nextStage($season));
    }

    /**
     * @param int $season
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function finishSeason(int $season)
    {
        return SeasonStatisticResource::collection($this->service->finishSeason($season));
    }
}
