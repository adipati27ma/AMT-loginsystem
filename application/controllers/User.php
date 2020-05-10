<?php

defined('BASEPATH') or exit('No direct script access allowed');

class User extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        is_logged_in();
    }

    public function index()
    {
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $data['title'] = 'My Profile';
        $data['view'] = 'index';

        $this->_showUI($data);
    }

    private function _showUI($data)
    {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('user/' . $data['view'], $data);
        $this->load->view('templates/footer');
    }

    public function edit()
    {

        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $data['title'] = 'Edit Profile';
        $data['view'] = 'edit';

        $this->form_validation->set_rules('name', 'Full Name', 'required|trim|min_length[4]', [
            "required" => "Full Name is required!",
        ]);

        if (!$this->form_validation->run()) {
            $this->_showUI($data);
        } else {
            $name = $this->input->post('name');
            $email = $this->input->post('email');

            // cek jika ada gambar yg di upload
            $upload_image = $_FILES['image']['name'];

            if($upload_image) {
                $config['allowed_types']    = 'gif|jpg|png|jpeg';
                $config['max_size']         = '2048';
                $config['upload_path']      = './assets/img/profile';

                // if there same name, CI otomatis nambahin angka dibelakangnya
                $this->load->library('upload', $config);

                if($this->upload->do_upload('image')) {
                    $old_image = $data['user']['image'];
                    if($old_image != 'default.png') {
                        // hapus, linknya harus lengkap jd pke FCPATH
                        unlink(FCPATH . 'assets/img/profile/' . $old_image);
                    }
                    
                    $new_image = $this->upload->data('file_name');
                    $this->db->set('image', $new_image);
                }
            }

            $this->db->set('name', $name);
            $this->db->where('email', $email);
            $this->db->update('user');

            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
            Your profile has been updated!</div>');
            redirect('user/edit');
        }
    }

    public function removePhoto()
    {
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $old_image = $data['user']['image'];
        $email = $data['user']['email'];
        
        if($old_image != 'default.png') {
            unlink(FCPATH . 'assets/img/profile/' . $old_image);

            $new_image = 'default.png';
            $this->db->set('image', $new_image);
            $this->db->where('email', $email);
            $this->db->update('user');

            $this->session->set_flashdata('message', '<div class="alert alert-warning" role="alert">
            Profile photo removed.</div>');
            redirect('user/edit');
        } else {
            redirect('user/edit');
        }
    }

    public function changePassword()
    {
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
        $data['title'] = 'Change Password';
        $data['view'] = 'changepassword';


        $this->form_validation->set_rules('current_password', 'Current Password', 'required|trim');
        $this->form_validation->set_rules('new_password1', 'New Password', 'required|trim|min_length[8]|matches[new_password2]');
        $this->form_validation->set_rules('new_password2', 'Repeat Password', 'required|trim|min_length[8]|matches[new_password1]');
        
        
        if (!$this->form_validation->run()) {

        $this->_showUI($data);
        } else {
            $currentpass = $this->input->post('current_password');
            $newpass = $this->input->post('new_password1');
            
            if(!password_verify($currentpass, $data['user']['password'])) {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                Current password is wrong!</div>');
                redirect('user/changepassword');
            } else {
                if($currentpass == $newpass) {
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                    New password cannot be the same as current password!</div>');
                    redirect('user/changepassword');
                } else {
                    // new password sudah ok
                    $password_hash = password_hash($newpass, PASSWORD_DEFAULT);

                    $this->db->set('password', $password_hash);
                    $this->db->where('email', $data['user']['email']);
                    $this->db->update('user');

                    $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
                    Password changed!</div>');
                    redirect('user/changepassword');
                }
            }
        }
    }
}