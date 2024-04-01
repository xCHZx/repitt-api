<?php

namespace Database\Seeders;

use App\Models\AccountStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleOwner = AccountStatus::create(
            [
                "name" => "Completo",
                "description" => "Todos los datos del registro llenados"
            ]);
    }
}
