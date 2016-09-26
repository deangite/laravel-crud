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

	public $tableName = '';
	public $tableNameSingular = '';
	public $controllerName = '';
	public $fillable = '';
	public $tbData = [];
	public $inputs = [];
	public $cols = [];
	public $relationships = [];
	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
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
		$this->tableName = $this->getTableName();
		$this->tableNameSingular = ucfirst(str_singular($this->tableName));

		do {
			$relationship = $this->output->choice('Choice relationships', ['no','HasOne','HasMany','BelongsTo','ManyToMany'], 'no');
			if ($relationship == "no") break;
			$this->$relationship();
		} while ($relationship !== "no");

		do {
			$type = $this->output->choice('Enter column type', ['no','string','email','textarea','integer'], 'no');
			if ($type == "no") break;
			$this->$type();
		} while ($type !== "no");

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
		$this->relationships = implode("\n\n\t", $this->relationships);

		$array = array(
			'%%CLASSNAME%%',
			'%%TBNAME%%',
			'%%COLUMNS%%',
			'%%MODELNAME%%',
			'%%CNAME%%',
			'%%TBSINGULAR%%',
			'%%TBDATA%%',
			'%%FORMINPUTS%%',
			'%%FILLABLE%%',
			'%%RELATIONSHIPS%%',
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
			$this->fillable,
			$this->relationships,
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
		$name = $this->getColumnName();
		$this->tbData[] = '<td>{{ $'.str_singular($this->tableName).'->'.$name.' }}</td>';
		$this->fillable .= "'{$name}',";

		$this->col[] = '$table->string(\''. $name .'\')'.$this->getUnique().';';

		if($this->getIndex())
			$this->col[] = '$table->index(\''. $name .'\');';

		$this->inputs[] = '{!! Form::text(\''. $name .'\', '.$this->getDefault().', array(\'class\' => \'form-control\''.$this->getPlaceholder().')) !!}<br>';
	}

	public function email()
	{
		$name = $this->getColumnName();
		$this->tbData[] = '<td>{{ $'.str_singular($this->tableName).'->'.$name.' }}</td>';
		$this->fillable .= "'{$name}',";
		
		$this->col[] = '$table->string(\''. $name .'\')'.$this->getUnique().';';

		if($this->getIndex())
			$this->col[] = '$table->index(\''. $name .'\');';

		$this->inputs[] = '{!! Form::email(\''. $name .'\', '.$this->getDefault().', array(\'class\' => \'form-control\''.$this->getPlaceholder().')) !!}<br>';
	}

	public function textarea()
	{
		$name = $this->getColumnName();
		$this->tbData[] = '<td>{{ $'.str_singular($this->tableName).'->'.$name.' }}</td>';
		$this->fillable .= "'{$name}',";
		$this->col[] = '$table->text(\''. $name .'\');';

		$this->inputs[] = '{!! Form::textarea(\''. $name .'\', '.$this->getDefault().', array(\'class\' => \'form-control\''.$this->getPlaceholder().')) !!}<br>';
	}

	public function integer()
	{
		$name = $this->getColumnName();
		$this->tbData[] = '<td>{{ $'.str_singular($this->tableName).'->'.$name.' }}</td>';
		$this->fillable .= "'{$name}',";
		
		$this->col[] = '$table->integer(\''. $name .'\')'.$this->getUnique().$this->getUnsigned().';';

		if($this->getIndex())
			$this->col[] = '$table->index(\''. $name .'\');';

		$this->inputs[] = '{!! Form::number(\''. $name .'\', '.$this->getDefault().', array(\'class\' => \'form-control\''.$this->getPlaceholder().')) !!}<br>';
	}

	public function HasOne()
	{
		$table = $this->getTableName();
		$primaryKey = 'id';
		$foreignKey = $this->output->ask('Foreign Key in '.$table.'?',str_singular($this->tableName).'_'.$primaryKey);
		$this->relationships[] = 'public function '.str_singular($table).'()
		{
			return $this->hasOne(\'App\Models\\'.ucfirst(str_singular($table)).'\',\''.$foreignKey.'\',\'id\');
		}';
	}

	public function HasMany()
	{
		$table = $this->getTableName();
		$primaryKey = 'id';
		$foreignKey = $this->output->ask('Foreign Key in '.$table.'?',str_singular($this->tableName).'_'.$primaryKey);
		$this->relationships[] = 'public function '.$table.'()
		{
			return $this->hasMany(\'App\Models\\'.ucfirst(str_singular($table)).'\',\''.$foreignKey.'\',\'id\');
		}';
	}

	public function BelongsTo()
	{
		$table = $this->getTableName();
		$primaryKey = 'id';
		$foreignKey = $this->output->ask('Foreign Key in '.$this->tableName.'?',str_singular($table).'_'.$primaryKey);
		$this->col[] = '$table->integer(\''. $foreignKey .'\')->unsigned();';
		$this->col[] = '$table->foreign(\''. $foreignKey .'\')->references(\'id\')->on(\''.$table.'\');';
		$this->relationships[] = 'public function '.str_singular($table).'()
		{
			return $this->belongsTo(\'App\Models\\'.ucfirst(str_singular($table)).'\',\''.$foreignKey.'\',\'id\');
		}';
	}

	public function ManyToMany()
	{
		$table = $this->getTableName();
		$primaryKey = 'id';
		$midleTable = str_singular($this->tableName).'_'.str_singular($table);
		$midleTable = $this->output->ask('Midle Table Name?',$midleTable);
		$foreignKey1 = $this->output->ask('Foreign Key 1 in '.$midleTable.'?',str_singular($this->tableName).'_'.$primaryKey);
		$foreignKey2 = $this->output->ask('Foreign Key 2 in '.$midleTable.'?',str_singular($table).'_'.$primaryKey);

		if (empty(glob(database_path('migrations/[\d_]{17}_create_'.$midleTable.'_table.php')))) {
			$cols[] = '$table->integer(\''. $foreignKey1 .'\')->unsigned();';
			$cols[] = '$table->foreign(\''. $foreignKey1 .'\')->references(\'id\')->on(\''.$this->tableName.'\');';
			$cols[] = '$table->integer(\''. $foreignKey2 .'\')->unsigned();';
			$cols[] = '$table->foreign(\''. $foreignKey2 .'\')->references(\'id\')->on(\''.$table.'\');';
			
			$cols = implode("\n\t\t\t", $cols);

			$array = array(
				'%%CLASSNAME%%',
				'%%TBNAME%%',
				'%%COLUMNS%%',
			);
			$classname = array_map('ucfirst', explode('_', $midleTable));
			$classname = implode('', $classname);
			$arrayReplace = array(
				'Create'.$classname.'Table',
				$midleTable,
				$cols,
			);

			$txt = str_replace($array, $arrayReplace, $this->file);

			file_put_contents(database_path('migrations/'.date('Y_m_d_His').'_create_'.$midleTable.'_table.php'), $txt);
		}

		$this->relationships[] = 'public function '.str_singular($table).'()
		{
			return $this->belongsToMany(\'App\Models\\'.ucfirst(str_singular($table)).'\',\''.$midleTable.'\',\''.$foreignKey1.'\',\''.$foreignKey2.'\');
		}';
	}

	public function getTableName()
	{
		return $this->output->ask('Enter table name');
	}

	public function getColumnName()
	{
		return $this->output->ask('Column name');
	}

	public function getUnique()
	{
		return ($this->output->confirm('Unique?', false)) ? '->unique()' : '';
	}

	public function getUnsigned()
	{
		return ($this->output->confirm('Unsigned?', false)) ? '->unsigned()' : '';
	}

	public function getIndex()
	{
		return $this->output->confirm('Index?', false);
	}

	public function getDefault()
	{
		$default = $this->output->ask('Value default','no');
		return (in_array($default, ['n','no'])) ? 'null' : '\''.$default.'\'';
	}

	public function getPlaceholder()
	{
		$placeholder = $this->output->ask('Placeholder','no');
		return (in_array($placeholder, ['n','no'])) ? '' : ',\'placeholder\' => \''.$placeholder.'\'';
	}
}
