<?php


namespace App\Services;


/**
 * Class AbstractService
 * @package App\Services
 */
abstract class AbstractService
{
    /**
     * @var string
     */
    protected string $model;

    /**
     * @return mixed
     */
    public function getAll()
    {
        return $this->model::query()->getAll();
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function store(array $data)
    {
        return $this->model::query()->store($data);
    }

    /**
     * @param int $key
     * @param array $data
     * @return mixed
     */
    public function update(int $key, array $data)
    {
        return $this->model::query()->update($key, $data);
    }

    /**
     * @param int $key
     * @return mixed
     */
    public function show(int $key)
    {
        return $this->model::query()->findOrFail($key);
    }

    /**
     * @param int $key
     * @return mixed
     */
    public function delete(int $key)
    {
        return $this->model::query()->where('id', $key)->delete($key);
    }
}
