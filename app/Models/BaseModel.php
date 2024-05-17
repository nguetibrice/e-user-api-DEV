<?php

namespace App\Models;

trait BaseModel
{
    public function getCacheKey()
    {
        $table = strtolower($this->getTable());
        return $table . ":" . $this->id;
    }
}
