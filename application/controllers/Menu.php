<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Menu extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        is_logged_in();
        $this->load->model('Menu_model', 'menu');
    }
    
    public function index()
    {
        // karena dikit jd di akhir pake row_array()
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $data['title'] = 'Menu Management';
        $data['menu'] = $this->menu->getAllMenu();
        $data['method'] = 'index';


        $this->form_validation->set_rules('menu', 'Menu', 'required|trim' , [
            'required' => 'Menu field is required!'
        ]);
        
        // form_validation sudah di set di autoload
        if (!($this->form_validation->run())) {
            $this->_showUI($data);
        } else {
            $menu = $this->input->post('menu');
            $this->menu->addMenu($menu);
            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
            New menu added!</div>');
            redirect('menu');
        }
    }

    private function _showUI($data)
    {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('menu/' . $data['method'], $data);
        $this->load->view('templates/footer');
    }

    public function submenu()
    {
        $data['title'] = 'Submenu Management';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $data['submenu'] = $this->menu->getAllSubmenu();
        $data['menu'] = $this->menu->getAllMenu();
        $data['method'] = 'submenu';

        $this->form_validation->set_rules('title', 'Title', 'required|trim' , [
            'required' => 'Title field is required!'
        ]);
        $this->form_validation->set_rules('menu_id', 'Menu', 'required|trim' , [
            'required' => 'Menu field is required!'
        ]);
        $this->form_validation->set_rules('url', 'URL', 'required|trim' , [
            'required' => 'URL field is required!'
        ]);
        $this->form_validation->set_rules('icon', 'Icon', 'required|trim' , [
            'required' => 'Icon field is required!'
        ]);

        if(!($this->form_validation->run())) {
            $this->_showUI($data);
        } else {
            $data = [
                'title' => $this->input->post('title'),
                'menu_id' => $this->input->post('menu_id'),
                'url' => $this->input->post('url'),
                'icon' => $this->input->post('icon'),
                'is_active' => $this->input->post('is_active')
            ];
            $this->db->insert('user_sub_menu', $data);
            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
            New sub menu added!</div>');
            redirect('menu/submenu');
        }
    }

    public function deleteSubmenu($id)
    {
        $this->menu->deleteSubMenu($id);
        $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
        Sub menu has been deleted!</div>');
        redirect('menu/submenu');
    }
    
    public function deleteMenu($id)
    {
        $this->menu->deleteMenu($id);
        $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
        Menu has been deleted!</div>');
        redirect('menu');
    }

    public function getMenu($idMenu)
    {
        // $idMenu = $this->input->post('id'); // tidak dipakai karena memakai parameter utk tangkap id-nya
        $dataMenu = $this->db->get_where('user_menu', ['id' => $idMenu])->row_array();
        echo json_encode($dataMenu);
    }

    public function getSubmenu($idSubmenu)
    {
        $dataSubmenu = $this->db->get_where('user_sub_menu', ['id' => $idSubmenu])->row_array();
        echo json_encode($dataSubmenu);
    }

    public function editMenu($id)
    {
        $this->form_validation->set_rules('menu', 'Menu', 'required|trim' , [
            'required' => 'Menu field is required!'
        ]);
        if ($this->form_validation->run()) {
        $this->menu->editMenu($id);
        $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
        Menu name successfully changed!</div>'); // ini PHP WOY, gabisa pake string LITERAL!!
        redirect('menu');
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
            Failed to change Menu name.</div>');
            redirect('menu');
        }
    }

    public function editSubmenu($id)
    {
        $this->form_validation->set_rules('title', 'Title', 'required|trim' , [
            'required' => 'Title field is required!'
        ]);
        $this->form_validation->set_rules('menu_id', 'Menu', 'required|trim' , [
            'required' => 'Menu field is required!'
        ]);
        $this->form_validation->set_rules('url', 'URL', 'required|trim' , [
            'required' => 'URL field is required!'
        ]);
        $this->form_validation->set_rules('icon', 'Icon', 'required|trim' , [
            'required' => 'Icon field is required!'
        ]);


        if ($this->form_validation->run()) {
        $this->menu->editSubmenu($id);
        $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
        Sub menu successfully edited!</div>'); // ini PHP WOY, gabisa pake string LITERAL!!
        redirect('menu/submenu');
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
            Failed to edit Submenu.</div>');
            redirect('menu/submenu');
        }
    }
}