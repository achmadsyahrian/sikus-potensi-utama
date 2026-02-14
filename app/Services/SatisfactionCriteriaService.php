<?php

namespace App\Services;

use App\Models\SatisfactionCriterion;

class SatisfactionCriteriaService
{
    public function createCriteria(array $data)
    {
        return SatisfactionCriterion::create($data);
    }

    public function updateCriteria(SatisfactionCriterion $criterion, array $data)
    {
        $criterion->update($data);
        return $criterion;
    }

    public function deleteCriteria(SatisfactionCriterion $criterion)
    {
        return $criterion->delete();
    }
}
