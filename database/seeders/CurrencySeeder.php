<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('currencies')->insert([
            'code' => 'XAF',
            'name' => 'Central African CFA Franc',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        DB::table('currencies')->insert([
            'code' => 'USD',
            'name' => 'United States Dollar',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        DB::table('currencies')->insert([
            'code' => 'CAD',
            'name' => 'Canadian Dollar',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        DB::table('currencies')->insert([
            'code' => 'EUR',
            'name' => 'Euro',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
