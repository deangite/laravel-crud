<?php

namespace InveenLaracrud;

use Illuminate\Console\Command;

class LaraCrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel:crud';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return mixed
     */
    public function handle()
    {
        $tableName = $this->output->ask('Enter table name');
        $tableNameSingular = ucfirst(str_singular($tableName));

        $columns = [];
        $cols = [];

        do {
            $column = $this->output->ask('Enter column name', 'No');
            if ($column == "No") break;
            $type = $this->output->ask('type', 'string');
            $columns[$type][] = $column;
            $cols[] = $column;
        } while ($column !== "No");

        $tbData = '';
        $inputs = '';
        $fillable = '';
        foreach($cols as $col){
            $tbData .= '<td>{{ $'.str_singular($tableName).'->'.$col.' }}</td>__';
            $inputs .= '{!! Form::text(\''. $col .'\', null, array(\'class\' => \'form-control\')) !!}<br>__';
            $fillable .= "'{$col}',";
        }

        $tbData .= '<td>
                        <a href="{{ route(\''. $tableName .'.edit\', [$'.str_singular($tableName).'->id]) }}">Edit</a>
                        <a href="{{ route(\''. $tableName .'.delete\', [$'.str_singular($tableName).'->id]) }}">Delete</a>
                    </td>';

        $file        = file_get_contents(__DIR__.'/files/migration.txt');
        $controller  = file_get_contents(__DIR__.'/files/controller.txt');
        $model       = file_get_contents(__DIR__.'/files/model.txt');
        $viewIndex   = file_get_contents(__DIR__.'/files/index.txt');
        $viewCreate  = file_get_contents(__DIR__.'/files/create.txt');
        $viewEdit    = file_get_contents(__DIR__.'/files/edit.txt');

        $col = '';
        foreach($columns as $key => $cs){
            foreach($cs as $c){
                $col .= '$table->'.$key.'(\''. $c .'\');__';
            }
        }

        $cols = str_replace('__', "\n", $col);
        $tbData = str_replace('__', "\n", $tbData);

        $inputs = str_replace('__', "\n", $inputs);

        $array = array(
            '%%CLASSNAME%%',
            '%%TBNAME%%',
            '%%COLUMNS%%',
            '%%MODELNAME%%',
            '%%CNAME%%',
            '%%TBSINGULAR%%',
            '%%TBDATA%%',
            '%%FORMINPUTS%%',
            '%%FILLABLE%%'
        );

        $controllerName = ucfirst($tableName);
        $arrayReplace = array(
            'Create'.ucfirst($tableName).'Table',
            $tableName,
            $cols,
            $tableNameSingular,
            $controllerName,
            str_singular($tableName),
            $tbData,
            $inputs,
            $fillable
        );

        $txt = str_replace($array, $arrayReplace, $file);
        $con = str_replace($array, $arrayReplace, $controller);
        $mod = str_replace($array, $arrayReplace, $model);
        $indexView  = str_replace($array, $arrayReplace, $viewIndex);
        $createView = str_replace($array, $arrayReplace, $viewCreate);
        $editView = str_replace($array, $arrayReplace, $viewEdit);

        $routes = "__Route::get('{$tableName}', ['as' => '{$tableName}.index', 'uses' => '{$controllerName}Controller@index']);__";
        $routes .= "Route::get('{$tableName}/create', ['as' => '{$tableName}.create', 'uses' => '{$controllerName}Controller@create']);__";
        $routes .= "Route::post('{$tableName}', ['as' => '{$tableName}.store', 'uses' => '{$controllerName}Controller@store']);__";
        $routes .= "Route::get('{$tableName}/delete/{id}', ['as' => '{$tableName}.delete', 'uses' => '{$controllerName}Controller@delete']);__";
        $routes .= "Route::get('{$tableName}/{id}/edit', ['as' => '{$tableName}.edit', 'uses' => '{$controllerName}Controller@edit']);__";
        $routes .= "Route::post('{$tableName}/{id}', ['as' => '{$tableName}.update', 'uses' => '{$controllerName}Controller@update']);__";

        $routes = str_replace('__', "\n", $routes);

        if (!file_exists(app_path('Models'))) {
            mkdir(app_path('Models'), 0777, true);
        }

        if (!file_exists(resource_path('views/'.$tableName))) {
            mkdir(resource_path('views/'.$tableName), 0777, true);
        }

        file_put_contents(database_path('migrations/'.date('Y_m_d_His').'_create_'.$tableName.'_table.php'), $txt);
        file_put_contents(app_path('Http/Controllers/'.$controllerName.'Controller.php'), $con);
        file_put_contents(app_path('Models/'.$tableNameSingular.'.php'), $mod);
        file_put_contents(resource_path('views/'.$tableName.'/index.blade.php'), $indexView);
        file_put_contents(resource_path('views/'.$tableName.'/create.blade.php'), $createView);
        file_put_contents(resource_path('views/'.$tableName.'/edit.blade.php'), $editView);

        file_put_contents(app_path('Http/routes.php'), $routes, FILE_APPEND);

    }
}
