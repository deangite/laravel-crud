<?php

namespace Deangite\LaravelCrud;

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

	public $tableName = '';
	public $tableNameSingular = '';
	public $controllerName = '';
	public $fillable = '';
	public $tbData = [];
	public $inputs = [];
	public $col = [];
	public $columns = [];
	public $cols = [];

	public function __construct()
	{
		parent::__construct();

		$this->file        = file_get_contents(__DIR__.'/files/migration.txt');
		$this->controller  = file_get_contents(__DIR__.'/files/controller.txt');
		$this->model       = file_get_contents(__DIR__.'/files/model.txt');
		$this->viewIndex   = file_get_contents(__DIR__.'/files/index.txt');
		$this->viewCreate  = file_get_contents(__DIR__.'/files/create.txt');
		$this->viewEdit    = file_get_contents(__DIR__.'/files/edit.txt');
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		$this->tableName = $this->output->ask('Enter table name');
		$this->tableNameSingular = ucfirst(str_singular($this->tableName));


		do {
			$type = $this->output->ask('Enter column type', 'No');
			if ($type == "No") break;
			$this->$type();
		} while ($type !== "No");

		$this->tbData[] = '<td>'."\n\t\t\t\t\t\t\t\t".'<a href="{{ route(\''. $this->tableName .'.edit\', [$'.str_singular($this->tableName).'->id]) }}">Edit</a>'."\n\t\t\t\t\t\t\t\t".'<a href="{{ route(\''. $this->tableName .'.delete\', [$'.str_singular($this->tableName).'->id]) }}">Delete</a>'."\n\t\t\t\t\t\t\t".'</td>';

		$routes = "__Route::get('{$this->tableName}', ['as' => '{$this->tableName}.index', 'uses' => '{$this->controllerName}Controller@index']);__";
		$routes .= "Route::get('{$this->tableName}/create', ['as' => '{$this->tableName}.create', 'uses' => '{$this->controllerName}Controller@create']);__";
		$routes .= "Route::post('{$this->tableName}', ['as' => '{$this->tableName}.store', 'uses' => '{$this->controllerName}Controller@store']);__";
		$routes .= "Route::get('{$this->tableName}/delete/{id}', ['as' => '{$this->tableName}.delete', 'uses' => '{$this->controllerName}Controller@delete']);__";
		$routes .= "Route::get('{$this->tableName}/{id}/edit', ['as' => '{$this->tableName}.edit', 'uses' => '{$this->controllerName}Controller@edit']);__";
		$routes .= "Route::post('{$this->tableName}/{id}', ['as' => '{$this->tableName}.update', 'uses' => '{$this->controllerName}Controller@update']);__";

		$routes = str_replace('__', "\n", $routes);
		$this->cols = implode("\n\t\t\t", $this->col);
		$this->tbData = implode("\n\t\t\t\t\t\t\t", $this->tbData);
		$this->inputs = implode("\n\t\t\t\t\t", $this->inputs);

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

		$this->controllerName = ucfirst($this->tableName);
		$arrayReplace = array(
			'Create'.ucfirst($this->tableName).'Table',
			$this->tableName,
			$this->cols,
			$this->tableNameSingular,
			$this->controllerName,
			str_singular($this->tableName),
			$this->tbData,
			$this->inputs,
			$this->fillable
		);

		$txt = str_replace($array, $arrayReplace, $this->file);
		$con = str_replace($array, $arrayReplace, $this->controller);
		$mod = str_replace($array, $arrayReplace, $this->model);
		$indexView  = str_replace($array, $arrayReplace, $this->viewIndex);
		$createView = str_replace($array, $arrayReplace, $this->viewCreate);
		$editView = str_replace($array, $arrayReplace, $this->viewEdit);

		if (!file_exists(app_path('Models'))) {
			mkdir(app_path('Models'), 0777, true);
		}

		if (!file_exists(resource_path('views/'.$this->tableName))) {
			mkdir(resource_path('views/'.$this->tableName), 0777, true);
		}

		file_put_contents(database_path('migrations/'.date('Y_m_d_His').'_create_'.$this->tableName.'_table.php'), $txt);
		file_put_contents(app_path('Http/Controllers/'.$this->controllerName.'Controller.php'), $con);
		file_put_contents(app_path('Models/'.$this->tableNameSingular.'.php'), $mod);
		file_put_contents(resource_path('views/'.$this->tableName.'/index.blade.php'), $indexView);
		file_put_contents(resource_path('views/'.$this->tableName.'/create.blade.php'), $createView);
		file_put_contents(resource_path('views/'.$this->tableName.'/edit.blade.php'), $editView);

		file_put_contents(app_path('Http/routes.php'), $routes, FILE_APPEND);

	}

	public function string()
	{
		$name = $this->output->ask('Column name');
		$this->columns['string'][] = $name;
		$this->cols[] = $name;

		$this->col[] = '$table->string(\''. $name .'\');';
		$this->tbData[] = '<td>{{ $'.str_singular($this->tableName).'->'.$name.' }}</td>';
		$this->inputs[] = '{!! Form::text(\''. $name .'\', null, array(\'class\' => \'form-control\')) !!}<br>';
		$this->fillable .= "'{$name}',";
	}

	public function __call($type,$arguments)
	{
		$this->info($type.' is not permited, only: string');
		return false;
	}
}
