<?php


namespace App\Services;


use App\Models\Team;

/**
 * Class TeamService
 * @package App\Services
 */
class TeamService extends AbstractService
{
    /**
     * @var string
     */
    protected string $model = Team::class;
}
