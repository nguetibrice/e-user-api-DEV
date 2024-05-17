<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Model;
use App\Exceptions\ModelNotFoundException;

interface IBaseService
{
    /**
     * @return Model
     * @throws ModelNotFoundException
     */
    public function getModel(): Model;

    /**
     * @param string $key
     * @param mixed $value
     * @return Model
     * @throws ModelNotFoundException
     */
    public function findOneBy(string $key, $value);

    /**
     * @param string $id
     * @return Model
     * @throws ModelNotFoundException
     */
    public function findOneById($id);

    /**
     * @param Model $model
     * @throws ModelException
     */
    public function insert(Model $model);

    /**
     * @param Model $model
     * @throws ModelException
     */
    public function update(Model $model);

    /**
     * @param Model $model
     * @throws ModelException
     */
    public function delete(Model $model);
}
