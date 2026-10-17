<?php

namespace App\Traits;

use App\Services\DocumentNumber;
use Illuminate\Database\Eloquent\Builder;

trait HasSharedShipmentId
{
    protected function performInsert(Builder $query)
    {
        if (!$this->getKey() && in_array($this->getTable(), DocumentNumber::SHARED_ID_TABLES, true)) {
            $id = DocumentNumber::nextSharedId($this->getConnection());
            if ($id) {
                $this->setAttribute($this->getKeyName(), $id);
            }
        }

        return parent::performInsert($query);
    }
}
