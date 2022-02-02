<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MigrateDataFromCSV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:migrate_data {filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will help you migrate your csv data to database';
    protected $report_array = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $csv = Storage::path('public/' . $this->argument('filename'));

        if (($handle = fopen ( $csv, 'r' )) !== FALSE) {

            fgets($handle);  // (skip header)

            while ( ($data = fgetcsv ( $handle, 1000, ',')) !== FALSE ) {

                $name = explode(' ', $data [1]);

                $csv_data = [];
                $csv_data['id'] = (int) $data [0];
                $csv_data['name'] = $name [0];
                $csv_data['surname'] = $name [1];
                $csv_data['email'] = $data [2];
                $csv_data['age'] = (int) $data [3];
                $csv_data['location'] = $data [4];


                $this->validate($csv_data);
            }
            fclose ( $handle );
            $this->save_report();
        }
    }

    /**
     * @param array $csv_data
     * @return bool
     */
    public function validate(array $csv_data): bool
    {
        $validator = Validator::make($csv_data, [
            'id' => 'required|int|unique:customers',
            'name' => 'required|max:255',
            'surname' => 'required|max:255',
            'email' => 'required|email:rfc,dns|max:255',
            'age' => 'required|int|min:18|max:99',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            foreach ($errors->keys() as $error_key) {
                $csv_data []= $error_key;
            }
            $this->add_to_report($csv_data);
            return false;
        } else {
            $this->save($csv_data);
            return true;
        }
    }

    /**
     * @param array $csv_data
     * @return bool
     */
    public function save(array $csv_data) {
        $country = Country::where('name', $csv_data['location'])->first();
        if ($country) {
            $csv_data['country_code'] = $country->code;
        } else {
            $csv_data['country_code'] = null;
            $csv_data['location'] = 'Unknown';
        }

        Customer::create($csv_data);

        return true;
    }

    /**
     * @param $csv_data
     * @return void
     */
    public function add_to_report($csv_data) {
        $this->report_array []= $csv_data;
    }

    /**
     * @return void
     */
    public function save_report() {
        $report_path = Storage::path('public/report_' . time() . '.csv');

        $fp = fopen($report_path, 'w');

        $headers = [
            'id',
            'name',
            'surname',
            'email',
            'age',
            'location',
            'error_field'
        ];

        fputcsv($fp, $headers);
        foreach ($this->report_array as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
    }
}
