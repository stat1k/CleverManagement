<?php

namespace app\engine\controllers;

use app\engine\controllers\CommonController;
use Walrus\core\WalrusForm;

/**
 * Class StepController
 * @package engine\controllers
 */
class StepController extends CommonController
{
    public function show($id_project, $id_step)
    {
        if (empty($_SESSION)) {
            $this->go('/CleverManagement/');
        }
        else
        {
            $step = $this->model('step')->show($id_project, $id_step);

            $this->register('step', $step);
            $this->register('project_id', $id_project);
            $this->register('step_id', $id_step);

            $task = $this->model('task')->index($id_project, $id_step);
            if (empty($task))
            {
                $this->register('message', 'Pas de tâches trouvées');
            }
            else
            {
                $this->register('message', 'Tâches :');
            }

            $this->register('tasks', $task);

            $status = $this->model('step')->status_task($id_step);
            $this->register('status', $status);

            $time_step = $this->model('step')->time_step($id_step);

            $this->register('hour_step', $time_step['hour']);
            $this->register('price_step', $time_step['price']);
            
            $this->userDirectories();

            $this->setView('show');
        }
    }

    public function create($id_project)
    {
        if (empty($_SESSION)) {
            $this->go('/CleverManagement/');
        }
        else
        {
            $this->userDirectories();
            $this->setView('create');

    	    $form = new WalrusForm('form_step_create');
            $formAction = '/clevermanagement/'.$id_project.'/step/create';
            $form->setForm('action', $formAction);
            $this->register('project_id', $id_project);
            $this->register('myFormCreate', $form->render());

            if (!empty($_POST)){
                
                if (!isset($form->check()['name']) && !isset($form->check()['description'])) 
                {
                    $res = $this->model('step')->create($id_project);
                    $this->go('/clevermanagement/'.$id_project.'/show');
                }
                elseif (isset($form->check()['description']) && !isset($form->check()['name'])) {
                    $this->register('errorName', '<div style="width: 400px;" class="alert alert-error">'.$form->check()['description'].'</div>');
                }
                elseif (isset($form->check()['name']) && !isset($form->check()['description'])) {
                    $this->register('errorName', '<div style="width: 
                        400px;" class="alert alert-error">'.$form->check()['name'].'</div>');
                }
                elseif (isset($form->check()['name']) && isset($form->check()['description'])) {
                    $this->register('errorName', '<div style="width: 
                        400px;" class="alert alert-error">'.$form->check()['name'].'</div>');
                }
            }
        }
    }

    public function delete($id_project, $id_step)
    {
        if (empty($_SESSION)) {
            $this->go('/CleverManagement/');
        }
        else
        {
            $res = $this->model('step')->delete($id_project, $id_step);
            $this->go('/CleverManagement/'.$id_project.'/show');
        }
    }

    public function edit($id_project, $id_step)
    {
        if (empty($_SESSION))
        {
            $this->go('/CleverManagement/');
        }
        else
        {
            $this->userDirectories();

            $this->setView('edit');

            $form = new WalrusForm('form_step_edit');
            $formAction = '/clevermanagement/'.$id_project.'/step/'.$id_step.'/edit';
            $form->setForm('action', $formAction);

            $step = $this->model('step')->find($id_step);

            foreach ($form->getFields() as $field => $arrayOfAttribute)
            {
                if($arrayOfAttribute['type'] == 'textarea')
                {
                    $arrayOfAttribute['text'] = $step->getProperties()[$field];
                }
                elseif ($arrayOfAttribute['type'] == 'date') 
                {
                    $arrayOfAttribute['value'] = date('Y-m-d',strtotime($step->getProperties()[$field]));
                
                }
                else
                {
                    $arrayOfAttribute['value'] = $step->getProperties()[$field];
                }
                $form->setFields($field, $arrayOfAttribute);
            }
            $this->register('project_id', $id_project);
            $this->register('step_id', $id_step);
            $this->register('myFormEdit', $form->render());

            if(!empty($_POST))
            {
                if (!isset($form->check()['name']) && !isset($form->check()['description'])) 
                {
                    $this->model('step')->edit($id_project, $id_step);
                    $this->go('/CleverManagement/'.$id_project.'/show');
                }
                elseif (isset($form->check()['description'])) {
                    $this->register('errorName', '<div style="width: 400px;" class="alert alert-error">'.$form->check()['description'].'</div>');
                }
                elseif (isset($form->check()['name'])) {
                    $this->register('errorName', '<div style="width: 
                        400px;" class="alert alert-error">'.$form->check()['name'].'</div>');
                }
            }
        }
    }
}
