<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TasksModel;
use App\Models\UsuariosModel;

class Main extends BaseController
{
    public function index()
    {
        $data = [];
        $tasks_model = new TasksModel();
        $data['tasks'] = $tasks_model->where('id_user', session()->id)->findAll();
        $data['datatables'] = true;
        return view('main', $data);
    }

    public function login()
    {
        if (session()->has('id')) {
            return redirect()->to('/');
        }
        $data = [];
        $validation_errors = session()->getFlashdata('validation_errors');

        if ($validation_errors) {
            $data['validation_errors'] = $validation_errors;
        }

        return view("login_frm", $data);
    }

    public function login_submit()
    {
        $validation = $this->validate(
        [
            'text_usuario' => 'required',
            'text_senha' => 'required',
        ],
        [
            'text_usuario' => [
                'required' => 'O campo usuário é obrigatório'
            ],
            'text_senha' => [
                'required' => 'O campo senha é obrigatório'
            ],
        ]);

        if (!$validation) {
            return redirect()->to('login')->withInput()->with('validation_errors', $this->validator->getErrors());
        }

        $usuario = $this->request->getPost('text_usuario');
        $senha = $this->request->getPost('text_senha');

        $usuarios_model = new UsuariosModel();
        $usuario_data = $usuarios_model->where('usuario', $usuario)->first();

        if (!$usuario_data) {
            return redirect()->to('login')->withInput()->with('login_error', 'Usuario ou senha inválidos');
        }

        if (!password_verify($senha, $usuario_data->senha)) {
            return redirect()->to('login')->withInput()->with('login_error', 'Usuario ou senha inválidos');
        }

        $session = session();
        $session->set(['id' => $usuario_data->id]);
        $session->set(['usuario' => $usuario_data->usuario]);

        return redirect()->to('/');
    }
    
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/');
    }

    public function new_task()
    {
        $data = [];
        $validation_errors = session()->getFlashdata('validation_errors');
        if($validation_errors){
            $data['validation_errors'] = $validation_errors;
        }

        return view("new_task_frm", $data);
    }

    public function new_task_submit()
    {
        $validation = $this->validate([
            'text_tarefa' => [
                'label' => 'Nome da tarefa',
                'rules' => 'required|min_length[5]|max_length[200]',
                'errors' => [
                    'required' => 'O campo {field} é obrigatorio.',
                    'min_length' => 'O campo {field} deve ter no mínimo {param} caracteres.',
                    'max_length' => 'O campo {field} deve ter no máximo {param} caracteres.',
                ]
            ],
            'text_descricao' => [
                'label' => 'Descrição',
                'rules' => 'max_length[500]',
                'errors' => [
                    'max_length' => 'O campo {field} deve ter no máximo {param} caracteres.',
                ]
            ],
        ]);

        if(!$validation){
            return redirect()->back()->withInput()->with('validation_errors', $this->validator->getErrors());
        }

        $titulo = $this->request->getPost('text_tarefa');
        $descricao = $this->request->getPost('text_descricao');

        $tasks_model = new TasksModel();
        $tasks_model->insert([
            'id_user' => session()->id,
            'task_name' => $titulo,
            'task_description' => $descricao,
            'task_status' => 'new',
        ]);

        return redirect()->to('/');
    }

    public function search()
    {
        $data = [];
        $search_term = $this->request->getPost('text_search');

        $tasks_model = new TasksModel();
        $data['tasks'] = $tasks_model->where('id_user', session()->id)->like('task_name', $search_term)->findAll();
        $data['datatables'] = true;

        return view('main', $data);
    }

    public function filter($status)
    {
        $data = [];
        $tasks_model = new TasksModel();

        if ($status == 'all') {
            $data['tasks'] = $tasks_model->where('id_user', session()->id)->findAll();
        }else {
            $data['tasks'] = $tasks_model
            ->where('id_user', session()->id)
            ->where('task_status', $status)
            ->findAll();
        }

        $data['datatables'] = true;
        $data['status'] = $status;

        return view('main', $data);
    }

    public function edit_task($enc_id)
    {
        $task_id = decrypt($enc_id);
        if (!$task_id) {
            return redirect()->to('/');
        }

        $data = [];

        $validation_errors = session()->getFlashdata('validation_errors');
        if ($validation_errors) {
            $data['validation_errors'] = $validation_errors;
        }

        $tasks_model = new TasksModel();
        $task_data = $tasks_model->where('id', $task_id)->first();

        if (!$task_data) {
            return redirect()->to('/');
        }

        if ($task_data->id_user != session()->id) {
            return redirect()->to('/');
        }

        $data['task'] = $task_data;

        return view('edit_task_frm', $data);
    }

    public function edit_task_submit()
    {
        $validation = $this->validate([
            'hidden_id'=> [
                'label' => 'ID',
                'rules' => 'required',
                'errors' => [
                    'required' => 'O campo {field} é obrigatório'
                ],
            ],
            'text_tarefa' => [
                'label' => 'Nome da tarefa',
                'rules' => 'required|min_length[5]|max_length[200]',
                'errors' => [
                    'required' => 'O campo {field} é obrigatorio.',
                    'min_length' => 'O campo {field} deve ter no mínimo {param} caracteres.',
                    'max_length' => 'O campo {field} deve ter no máximo {param} caracteres.',
                ]
            ],
            'text_descricao' => [
                'label' => 'Descrição',
                'rules' => 'max_length[500]',
                'errors' => [
                    'max_length' => 'O campo {field} deve ter no máximo {param} caracteres.',
                ]
            ],
            'select_status'=> [
                'label' => 'Status',
                'rules' => 'required',
                'errors' => [
                    'required' => 'O campo {field} é obrigatório'
                ],
            ],
        ]);

        if (!$validation) {
            return redirect()->back()->withInput()->with('validation_errors', $this->validator->getError());
        }

        $task_id = decrypt($this->request->getPost('hidden_id'));
        if (!$task_id) {
            return redirect()->to('/');
        }

        $tarefa = $this->request->getPost('text_tarefa');
        $descricao = $this->request->getPost('text_descricao');
        $status = $this->request->getPost('select_status');

        $tasks_model = new TasksModel();
        $task_data = $tasks_model->where('id', $task_id)->first();
        if (!$task_data) {
            return redirect()->to('/');
        }

        if ($task_data->id_user != session()->id) {
            return redirect()->to('/');
        }

        $tasks_model->update($task_id, [
            'task_name' => $tarefa,
            'task_description' => $descricao,
            'task_status' => $status
        ]);

        return redirect()->to('/');
    }

    public function delete_task($enc_id)
    {
        $task_id = decrypt($enc_id);

        if (!$task_id) {
            return redirect()->to('/');
        }

        $tasks_model = new TasksModel();
        $task_data = $tasks_model->where('id', $task_id)->first();

        if (!$task_data) {
            return redirect()->to('/');
        }

        if ($task_data->id_user != session()->id) {
            return redirect()->to('/');
        }

        $data['task'] = $task_data;
        return view('delete_task', $data);
    }

    public function delete_task_confirm($enc_id)
    {
        $task_id = decrypt($enc_id);

        if (!$task_id) {
            return redirect()->to('/');
        }

        $tasks_model = new TasksModel();
        $task_data = $tasks_model->where('id', $task_id)->first();

        if (!$task_data) {
            return redirect()->to('/');
        }

        if ($task_data->id_user != session()->id) {
            return redirect()->to('/');
        }

        $tasks_model->delete($task_id);
        return redirect()->to('/');
    }

    public function task_details($enc_id)
    {
        $task_id = decrypt($enc_id);

        if (!$task_id) {
            return redirect()->to('/');
        }

        $tasks_model = new TasksModel();
        $task_data = $tasks_model->where('id', $task_id)->first();

        if (!$task_data) {
            return redirect()->to('/');
        }

        if ($task_data->id_user != session()->id) {
            return redirect()->to('/');
        }

        $data['task'] = $task_data;
        return view('task_details', $data);
    }

}
