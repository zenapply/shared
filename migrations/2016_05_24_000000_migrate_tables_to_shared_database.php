<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Symfony\Component\Process\Process;

class MigrateTablesToSharedDatabase extends Migration {

    public function __construct(){
        $this->setupConfig();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tables = [
            "assigned_roles",
            "companies",
            "company_modules",
            "company_products",
            "countries",
            "file_company",
            "file_user",
            "files",
            "modules",
            "password_reminders",
            "password_reset",
            "permission_role",
            "permissions",
            "products",
            "product_modules",
            "roles",
            "users",
        ];

        foreach ($tables as $table) {
            $success = $this->copyTableToDest($table);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }

    private function copyTableToDest($table)
    {
        $output = [];
        $result = null;

        $command = [];
        $command[] = "pg_dump -w -C";
        $command[] = "-t {$table}";
        $command[] = "-h ".$this->config['src']['host'];
        $command[] = "-U ".$this->config['src']['username'];
        $command[] = "-p ".$this->config['src']['port'];
        $command[] = $this->config['src']['database'];
        $command[] = "|";
        $command[] = "psql -w";
        $command[] = "-h ".$this->config['dest']['host'];
        $command[] = "-U ".$this->config['dest']['username'];
        $command[] = "-p ".$this->config['dest']['port'];
        $command[] = $this->config['dest']['database'];

        $command = implode(" ", $command);

        $process = $this->run($command);

        return $process->isSuccessful();
    }

    private function run($command){
        $tempFileHandle = tmpfile();
        fwrite($tempFileHandle, $this->getContentsOfCredentialsFile());
        $temporaryCredentialsFile = stream_get_meta_data($tempFileHandle)['uri'];

        $process = new Process($command, null, $this->getEnvironmentVariablesForDumpCommand($temporaryCredentialsFile));

        $process->run();

        return $process;
    }

    private function getContentsOfCredentialsFile(){
        $lines = [];

        foreach ($this->config as $conn => $props) {
            $contents = [
                $props['host'],
                $props['port'],
                $props['database'],
                $props['username'],
                $props['password'],
            ];

            $lines[] = implode(':', $contents);
        }
        return implode(PHP_EOL, $lines);
    }

    /**
     * @param $temporaryCredentialsFile
     *
     * @return array
     */
    private function getEnvironmentVariablesForDumpCommand($temporaryCredentialsFile)
    {
        return [
            'PGPASSFILE' => $temporaryCredentialsFile,
        ];
    }
    
    private function setupConfig(){

        $this->config = [];
        $connections = ['src' => config('database.default'), 'dest' => 'shared'];

        foreach($connections as $key => $value){
            $this->config[$key]['host']     = config("database.connections.{$value}.host");
            $this->config[$key]['database'] = config("database.connections.{$value}.database");
            $this->config[$key]['username'] = config("database.connections.{$value}.username");
            $this->config[$key]['port']     = config("database.connections.{$value}.port");
            $this->config[$key]['password'] = config("database.connections.{$value}.password");
        }

        foreach ($this->config as $conn => $props) {
            foreach ($props as $key => $value) {
                if(empty($value)){
                    throw new Exception("{$conn} -> {$key} is not configured!");
                }
            }
        }
    }
}