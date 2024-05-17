<?php

namespace App\Services;

use App\Exceptions\ModelException;
use App\Utils\CustomErrorMessages;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use App\Services\Contracts\IBaseService;
use App\Exceptions\ModelNotFoundException;
use App\Exceptions\CachedItemNotFoundException;

abstract class BaseService implements IBaseService
{
    /**
     * @var Model
     */
    protected Model $model;

    abstract protected function getModelObject(): Model;

    /**
     * @return Model
     * @throws ModelNotFoundException
     */
    public function getModel(): Model
    {
        if (!isset($this->model)) {
            throw new ModelNotFoundException("Model not loaded");
        }

        return $this->model;
    }

    /**
     * @param Model $model
     * @return void
     */
    public function setModel(Model $model): void
    {
        $this->model = $model;
    }

    /**
     * Model is unset by setting it to null
     */
    public function unsetModel(): void
    {
        unset($this->model);
    }

    /**
     * @param Model $model
     * @throws ModelException
     */
    public function insert(Model $model)
    {
        if (!$model->save()) {
            throw new ModelException('Failed to insert model');
        }

        $this->setModel($model);

        $this->findOneById($model);
    }

    /**
     * @param Model $model
     * @throws ModelException
     */
    public function update(Model $model)
    {
        if (!$model->update()) {
            throw new ModelException('Failed to update model');
        }

        Cache::forget($model->getCacheKey());

        $this->findOneById($model->id);

        $this->setModel($model);
    }

    /**
     * @param Model $model
     * @throws ModelException
     */
    public function delete(Model $model)
    {
        if (!$model->delete()) {
            throw new ModelException('Failed to delete model');
        }

        Cache::forget($model->getCacheKey());

        $this->unsetModel();
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return Model
     * @throws ModelNotFoundException
     */
    public function findOneBy(string $key, $value)
    {
        $table = strtolower($this->getModelObject()->getTable());
        $secondary_cache_key = $table . ":" . $key . ":" . $value;

        try {
            $main_cache_key = $this->getFromCache($secondary_cache_key);
            return $this->getFromCache($main_cache_key);
        } catch (CachedItemNotFoundException $e) {
            $model = $this->getModelObject()::where([$key => $value])->first();
            return $this->saveReferenceToCache($secondary_cache_key, $model, now()->addDay());
        }
    }

    /**
     * @param string $id
     * @return Model
     * @throws ModelNotFoundException
     */
    public function findOneById($id)
    {
        $table = strtolower($this->getModelObject()->getTable());
        $cache_key = $table . ":" . $id;

        $model = Cache::rememberForever($cache_key, function () use ($id) {
            return $this->getModelObject()::find($id);
        });

        if (!$model) {
            $error_message = CustomErrorMessages::interpolate(CustomErrorMessages::MODEL_NOT_FOUND, [
                'model' => get_class($this->getModelObject()),
                'key' => 'id',
                'value' => $id
            ]);
            throw new ModelNotFoundException($error_message);
        }

        return $model;
    }

    /**
     * @param string $secondaryKey
     * @param Model|null $model
     * @param \DateTimeInterface $expirationDate
     * @return Model
     * @throws ModelNotFoundException
     */
    protected function saveReferenceToCache(
        string $secondaryKey,
        ?Model $model,
        \DateTimeInterface $expirationDate
    ) {
        [, $key, $value] = explode(":", $secondaryKey);

        if (!$model) {
            $error_message = CustomErrorMessages::interpolate(CustomErrorMessages::MODEL_NOT_FOUND, [
                'model' => get_class($this->getModelObject()),
                'key' => $key,
                'value' => $value
            ]);
            throw new ModelNotFoundException($error_message);
        }

        $mainKey = $model->getCacheKey();

        Cache::put($mainKey, $model);
        Cache::put($secondaryKey, $mainKey, $expirationDate);

        return $model;
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function getFromCache(string $key)
    {
        $result = Cache::get($key);
        if (!$result) {
            throw new CachedItemNotFoundException("There is no item at $key");
        }

        return $result;
    }
}
