<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'description',
        'price', 'stock_quantity', 'sku', 'barcode', 'image'
    ];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function inventorySummaries()
    {
        return $this->hasMany(InventorySummary::class);
    }
}