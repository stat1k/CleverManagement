<?php

namespace app\engine\models;

use R;
use app\engine\models\Common;

class Directory extends Common
{
	public function getDirectory($id)
	{
		$directory = R::load('directories', $id);
		return $directory;
	}

	public function show($id)
	{
        $this->permission_access($id, 'directory');

        $relations = R::getAll('SELECT * FROM projects_directories WHERE id_directory = :directory',
            [':directory' => $id]
        );

        $projects = array();
        foreach ($relations as $key => $object) {
            $projects[] = R::load('projects', $object['id_project']);
            $this->status_step($object['id_project']);
        }

        return $projects;
		
	}

	public function create()
	{
        if(empty($_POST['name']))
        {
            return array('name.empty' => 'Name can\'t be empty');
        }

        if(!empty($errors))
            return $errors;

		$directory = R::dispense('directories');
        $directory->name = $_POST['name'];
        $directory->id_user = $_SESSION['user']['id'];
        R::store($directory);

        return true;
	}

	public function edit($id)
	{
        $this->permission_access($id, 'directory');

        $directory = R::load('directories', $id);
        $directory->name = $_POST['name'];
        R::store($directory);
		
        return $directory;
	}

	public function delete($id)
	{
        $this->permission_access($id, 'directory');

        $directory = R::load('directories', $id);

        R::trash($directory);
	}
}