<?php

namespace Database\Seeders;

use App\Models\Purchase;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    public function run()
    {
        Purchase::factory()->count(1)->create();
    }
}