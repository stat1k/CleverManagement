<?php

namespace app\engine\models;

use R;

class Project
{
    public function create()
    {
        // project creation
        $project = R::dispense('projects');
        $project->name = $_POST['name'];
        $project->startline = $_POST['startline'];
        $project->deadline = $_POST['deadline'];
        $project->description = $_POST['description'];
        $id = R::store($project);

        // link to current user as admin
        $user = R::load('users', $_SESSION['user']['id']);
        $newproject = R::load('projects', $id);
        $id_project = $newproject->getProperties()['id'];
        R::exec('INSERT INTO projects_users (id_user, id_project, admin) VALUES (:user, :project, 1)', array(
            ':user' => $user->getProperties()['id'], 
            ':project' => $id_project)
        );

        // link to specified members
        $membersErrors = $this->membersFlush($_POST['members'], $id_project, 0);
        $adminsErrors = $this->membersFlush($_POST['additionalAdmins'], $id_project, 1);

        return true;
    }

    /*
        Function user for member and admin select2 fields

        Connects given email addresses to project as member or admin

        return value : Array of email addresses which failed to connect to project
    */
    private function membersFlush($selects, $id_project, $boolAdmin)
    {
        $members = $this->parseSelects($selects);

        $memberErrors = array();
        foreach ($members as $value => $status) {
            if ($status === 1) {
                $member = R::findOne('users', 'email = ?', array($value));
                if ($member == null) {
                    $memberErrors[] = $value;
                    continue;
                }

                $exec = R::exec('INSERT INTO projects_users (id_user, id_project, admin) VALUES (:user, :project, :admin)', array(
                    ':user'     => $member->getProperties()['id'], 
                    ':project'  => $id_project,
                    ':admin'    => $boolAdmin
                ));
            } else {
                $memberErrors[] = $value;
            }
        }

        return $memberErrors;
    }

    /*
        Function used for member and admin select2 fields

        Checks email address pattern

        return value : Array of every email addresses given as key and 1 or 0 as value
        1 means email address is good
        0 means email address is wrong
    */
    private function parseSelects($selects)
    {
        $regex = '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/';
        $values = explode(',', $selects);
        $rightValues = array();
        $errors = array();
        foreach ($values as $key => $value) {
            if (preg_match($regex, $value) === 1) {
                $return[$value] = 1;
            } else {
                $return[$value] = 0;
            }
        }

        return $return;
    }

    public function show($id)
    {
        $project = R::load('projects', $id);

        if($project->getProperties()['id'] == 0)
        {
            return array('project.not_found' => 'project doesnt exist');
        }

        return $project;
    }

    public function index()
    {
        $user = R::load('users', $_SESSION['user']['id']);
        $relations = R::getAll('SELECT * FROM projects_users WHERE id_user = :user',
            [':user' => $user->getProperties()['id']]
        );
        
        $projects = array();
        foreach ($relations as $key => $object) {
            $projects[] = R::load('projects', $object['id_project']);
        }

        return $projects;
    }

    public function edit($id)
    {
        $project = R::load('projects', $id);

        if(empty($_POST['name']))
        {
            return array('name.empty' => 'Name can\'t be empty');
        }

        $project->name = $_POST['name'];
        $project->description = $_POST['description'];

        R::store($project);
        return $project;
    }

    public function delete($id)
    {
        $project = R::load('projects', $id);

        if($project->getProperties()['id'] == 0)
        {
            return array('project.not_found' => 'Project doesnt exist');
        }

        // if($_SESSION['user']['id'] != $project->getProperties()['users_id'])
        // {
        // return array('user.forbidden' => 'Vous n\'avez pas les droits de faire ca');
        // }

        R::trash($project);
    }
}