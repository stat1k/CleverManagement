<?php

namespace app\engine\controllers;
 
use app\engine\controllers\CommonController;
use Walrus\core\WalrusForm;
use Walrus\core\WalrusACL;

/**
* Class ProjectController
* @package engine\controllers
*/
class ProjectController extends CommonController
{
    public function index()
    {
        if (empty($_SESSION))
        {
            $this->go('/CleverManagement/signin');
        }
        else
        {
            $res = $this->model('project')->index();
            $projects = array();
            foreach ($res as $type => $array) {
                foreach ($array as $key => $object) {
                    $projects[$type][$key] = $object->getProperties();
                    $projects[$type][$key]['members'] = $members = $this->model('project')->retrieveUsers($array[$key]['id'], 'members');
                    $projects[$type][$key]['admins'] = $members = $this->model('project')->retrieveUsers($array[$key]['id'], 'additionalAdmins');
                }
            }

            $this->userDirectories();
            $this->register('projects', $projects);
            $this->setView('index');
        }
    }

    public function create()
    {
        if (empty($_SESSION))
        {
            $this->go('/CleverManagement/');
        }
        else
        {
            $form = new WalrusForm('form_project_create');
            $directories = $this->model('user')->getDirectoriesName();
            $form->setFieldValue('directory', 'options', $directories);
            $this->register('myFormCreate', $form->render());
            
            if(!empty($_POST))
            {
                if (isset($form->check()['name'])) 
                {
                    $this->register('errorName', '<div style="width: 
                        400px;" class="alert alert-error">'.$form->check()['name'].'</div>');
                }
                elseif (isset($form->check()['description'])) {
                    $this->register('errorName', '<div style="width: 400px;" class="alert alert-error">'.$form->check()['description'].'</div>');
                }
                else
                {
                     $this->model('project')->create();
                    $this->go('/CleverManagement');
                }
                
               
            }

            $this->userDirectories();
            $this->setView('create');

        }
    }

    public function edit($id)
    {
        if (empty($_SESSION)) {
            $this->go('/CleverManagement/');
            return;
        }

        $this->userDirectories();
        $this->setView('edit');

        $form = new WalrusForm('form_project_edit');
        $formAction = '/clevermanagement/'.$id.'/edit';
        $directories = $this->model('user')->getDirectoriesName();
        $form->setFieldValue('directory', 'options', $directories);

        $form->setForm('action', $formAction);

        $project = $this->model('project')->find($id);

        foreach ($form->getFields() as $field => $arrayOfAttributes) {
            if ($field == 'members' || $field == 'additionalAdmins') {
                $usersEmail = $this->model('project')->retrieveUsersEmails($id, $field);
                $form->setFieldValue($field, 'value', implode(',', $usersEmail));
            } elseif($field == 'directory') {
                // selected=selected not supported by WalrusForm
            } elseif ($arrayOfAttributes['type'] == 'textarea') {
                $form->setFieldValue($field, 'text', $project->getProperties()[$field]);
            }
            elseif ($field == 'startline' || $field == 'deadline') {
                $form->setFieldValue($field, 'value', date('Y-m-d',strtotime($project->getProperties()[$field])));
            } else {
                $form->setFieldValue($field, 'value', $project->getProperties()[$field]);
            }
        }

        $this->register('myFormEdit', $form->render());

        if(!empty($_POST))
        {
            $res = $this->model('project')->edit($id);
            if (!isset($form->check()['name']) && !isset($form->check()['description'])) 
            {
                // $this->model('project')->create();
                $this->go('/CleverManagement');
            }
            elseif (isset($form->check()['description']))
            {
                $this->register('errorName', '<div style="width: 400px;" class="alert alert-error">'.$form->check()['description'].'</div>');
            }
            elseif (isset($form->check()['name']))
            {
                $this->register('errorName', '<div style="width: 
                    400px;" class="alert alert-error">'.$form->check()['name'].'</div>');
            }
        }

        $res = $this->model('project')->find($id);

        if(is_array($res))
        {
            $this->register('error', 'Project doesnt exist');
        }
    }

    public function delete($id)
    {
        if (empty($_SESSION)) {
            $this->go('/CleverManagement/');
        }
        else
        {
            $res = $this->model('project')->delete($id);

            $this->go('/CleverManagement');
        }
    }

    public function show($id)
    {
        if (empty($_SESSION))
        {
            $this->go('/CleverManagement/');
            return;
        }

        $res = $this->model('project')->show($id);
        $this->register('project', $res);

        $step = $this->model('step')->index($id);
        if (empty($step))
            $this->register('message', "Pas d'étape(s) trouvée(s) pour ce projet");
        else
            $this->register('message', 'Etapes :');

        $admins = $this->model('project')->retrieveUsers($id, 'additionalAdmins');
        $members = $this->model('project')->retrieveUsers($id, 'members');
        $status = $this->model('project')->status_step($id);
        $time_project = $this->model('project')->time_project($id);

        $this->userDirectories();
        $this->register('hour_project', $time_project['hour']);
        $this->register('price_project', $time_project['price']);
        $this->register('project_id', $id);
        $this->register('steps', $step);
        $this->register('admins', $admins);
        $this->register('members', $members);
        $this->register('status', $status);

        $this->setView('show');
    }
}
