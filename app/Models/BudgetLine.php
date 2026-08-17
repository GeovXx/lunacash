<?php

namespace App\Models;

class BudgetLine extends UserOwnedModel
{
    protected $table = 'budget_lines';

    protected $fillable = ['budget_id', 'category_id', 'name', 'planned_amount', 'currency', 'status', 'notes', 'metadata'];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
