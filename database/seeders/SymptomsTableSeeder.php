<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SymptomsTableSeeder extends Seeder
{
    public function run()
    {
        $content = file_get_contents(database_path('seeders/cleaned_list.txt'));
        $symptoms = array_map('trim', explode(',', str_replace("'", '', $content)));

        $batchSize = 500;
        $chunks = array_chunk($symptoms, $batchSize);

        foreach ($chunks as $chunk) {
            $insertData = array_map(function ($name) {
                return ['name' => $name, 'created_at' => now(), 'updated_at' => now()];
            }, $chunk);

            DB::table('symptoms')->insert($insertData);
        }
    }
}

