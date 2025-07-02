<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Log;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::truncate();
        $file = fopen(base_path("database/data-log.csv"), "r");
        while (($data = fgetcsv($file, 2000, ',')) !== FALSE) {
            $data = array_pad($data, 11, NULL);

            Log::create([
                'device_id' => Device::first()->id,
                'time_stamp' => isset($data[0]) ? \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $data[0])->format('Y-m-d H:i:s') : null,
                'volt' => !is_null($data[1]) && $data[1] != 'nan' ? $data[1] : 0,
                'ampere' => !is_null($data[2]) && $data[2] != 'nan' ? $data[2] : 0,
                'power' => !is_null($data[3]) && $data[3] != 'nan' ? $data[3] : 0,
                'energy' => !is_null($data[4]) && $data[4] != 'nan' ? $data[4] : 0,
                'frequency' => !is_null($data[5]) && $data[5] != 'nan' ? $data[5] : 0,
                'power_factor' => !is_null($data[6]) && $data[6] != 'nan' ? $data[6] : 0,
                'temperature' => !is_null($data[7]) && $data[7] != 'nan' ? $data[7] : 0,
                'humidity' => !is_null($data[8]) && $data[8] != 'nan' ? $data[8] : 0,
                'created_at' => isset($data[0]) ? \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $data[0])->format('Y-m-d H:i:s') : null,
                'updated_at' => isset($data[0]) ? \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $data[0])->format('Y-m-d H:i:s') : null,
            ]);
        }

        fclose($file);
    }
}
