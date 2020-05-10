<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }


    public function index()
    {
        if ($this->session->userdata('email')) {
            redirect('user');
        }
        
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email', [
            'required' => 'Email is required!',
            'valid_email' => 'Email must contain a valid email address.'
        ]);
        $this->form_validation->set_rules('password', 'Password', 'required|trim', [
            'required' => 'Password is required!'
        ]);

        if ($this->form_validation->run() == false) {

            $data['title'] = 'Login Page';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/login');
            $this->load->view('templates/auth_footer');
        } else {
            $this->_login();
        }
    }


    private function _login()
    {
        $email = $this->input->post('email');
        $password = $this->input->post('password');

        $user = $this->db->get_where('user', ['email' => $email])->row_array();
        
        if ($user) {
            // ada usernya dan jika aktif
            if ($user['is_active'] == 1) {
                // cek password
                if(password_verify($password, $user['password'])){
                    $data = [
                        'email' => $user['email'],
                        'role_id' => $user['role_id']
                    ];
                    $this->session->set_userdata($data);

                    if ($user['role_id'] == 1) {
                        redirect('admin');
                    } else {
                        redirect('user');
                    }
                } else {
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                    Wrong password!</div>');
                    redirect('auth');
                }
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-warning" role="alert">
                This email has not been activated!</div>');
                redirect('auth');
            }
        } else {
            // beri message
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
            Email is not registered!</div>');
            redirect('auth');
        }
    }


    public function registration()
    {
        if ($this->session->userdata('email')) {
            redirect('user');
        }

        $this->form_validation->set_rules('name', 'Name', 'required|trim|min_length[3]', [
            'required' => 'Name is required!'
        ]);
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|is_unique[user.email]', [
            'required' => 'Email is required!',
            'valid_email' => 'Email must contain a valid email address.',
            'is_unique' => 'This email has already registered!'
        ]);
        $this->form_validation->set_rules('password1', 'Password', 'required|trim|min_length[8]|matches[password2]', [
            'required' => 'Password is required!',
            'matches' => 'Password is not match!',
            'min_length' => 'Password is too short!'
        ]);
        $this->form_validation->set_rules('password2', 'Password', 'required|trim|matches[password1]');



        // agar form_valiadtion->run = true, harus ada rules dulu

        if ($this->form_validation->run() == false) {

            $data['title'] = "User Registration";
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/registration');
            $this->load->view('templates/auth_footer');
        } else {
            $name = $this->input->post('name', true);
            $email = $this->input->post('email', true);
            $password = $this->input->post('password1');

            $data = [
                'name' => htmlspecialchars($name), // "true" untuk menghindari injeksi XSS
                'email' => htmlspecialchars($email),
                'image' => 'default.png',
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role_id' => 2,
                'is_active' => 0,
                'date_created' => time()
            ];

            // siapkan token
            // base64 utk karakter aneh berubah jadi numerik&karakter saja
            $token = base64_encode(random_bytes(32));
            $user_token = [
                'email' => $email,
                'token' => $token,
                'date_created' => time()
            ];
            

            $this->db->insert('user', $data);
            $this->db->insert('user_token', $user_token);

            $this->_sendEmail($token, 'verify');
            
            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
            Congratulation! Your account has been created. <br>Please <b>activate</b> account from your email.</div>');
            redirect('auth');
        }
    }





    private function _sendEmail($token, $type)
    {
        $this->load->library('email', $config);

        $config = [
            'protocol' => 'smtp',
            'smtp_host' => 'ssl://smtp.googlemail.com',
            'smtp_user' => 'adipatimaruftech@gmail.com',
            'smtp_pass' => 'amtadminadministrator123',
            'smtp_port' => 465,
            'mailtype' => 'html',
            'charset' => 'utf-8',
        ];
        $this->email->set_newline("\r\n");

        $this->email->initialize($config);

        $this->email->from('adipatimaruftech@gmail.com', 'Admin AMT');
        $this->email->to($this->input->post('email'));

        if($type == 'verify') {
            $this->email->subject('Account Verification');
            // link verification
            $link = '<a href="' . base_url() . 'auth/verify?email=' . $this->input->post('email') . '&token=' . urlencode($token) .'">Activate</a>';
            $this->email->message('Click this link to verify your account : ' . $link);
        } else if ($type == 'forgot') {
            $this->email->subject('Reset Password');
            // link verification
            $link = '<a href="' . base_url() . 'auth/resetpassword?email=' . $this->input->post('email') . '&token=' . urlencode($token) .'">Reset Password</a>';
            $this->email->message('Click this link to reset your password : ' . $link);
        }

        if($this->email->send()) {
            return true;
        } else {
            echo $this->email->print_debugger();
            die;
        }
    }





    public function verify()
    {
        $email = $this->input->get('email');
        $token = $this->input->get('token');

        $user = $this->db->get_where('user', ['email' => $email])->row_array();

        if($user) {
            $user_token = $this->db->get_where('user_token', ['token' => $token])->row_array(); 

            if($user_token) {
                if(time() - $user_token['date_created'] < (60*60*24)) {
                    $this->db->set('is_active', 1);
                    $this->db->where('email', $email);
                    $this->db->update('user');

                    $this->db->delete('user_token', ['email' => $email]);

                    $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
                    '. $email . ' has been <b>activated</b>!<br>Please login</div>');
                    redirect('auth');
                } else {
                    $this->db->delete('user', ['email' => $email]);
                    $this->db->delete('user_token', ['email' => $email]);
                    
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                    Account activation failed! <b>Expired token</b>.</div>');
                    redirect('auth');
                }
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                Account activation failed! <b>Invalid token</b>.</div>');
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
            Account activation failed! <b>Wrong email</b>.</div>');
            redirect('auth');
        }
    }




    public function logout()
    {
        $this->session->unset_userdata('email');
        $this->session->unset_userdata('role_id');

        $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
        You have been logged out!</div>');
        redirect('auth');
    }

    
    public function blocked()
    {
        $this->load->view('auth/blocked');
    }



    public function forgotPassword()
    {
        if ($this->session->userdata('email')) {
            redirect('user');
        }
        
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
        
        if(!$this->form_validation->run()) {
            $data['title'] = 'Forgot Password';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/forgot-password');
            $this->load->view('templates/auth_footer');
        } else {
            $email = $this->input->post('email');
            $user = $this->db->get_where('user', ['email' => $email, 'is_active' => 1])->row_array();

            if($user) {
                $token = base64_encode(random_bytes(16));
                $user_token = [
                    'email' => $email,
                    'token' => $token,
                    'date_created' => time()
                ];

                $this->db->insert('user_token', $user_token);
                $this->_sendEmail($token, 'forgot');

                $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
                Please check your email to <b>reset</b> your password.</div>');
                redirect('auth/forgotpassword');
                
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                Email is <b>not found</b> or not <b>activated</b>!</div>');
                redirect('auth/forgotpassword');
            }
        }
    }



    public function resetPassword()
    {
        $email = $this->input->get('email');
        $token = $this->input->get('token');

        $user = $this->db->get_where('user', ['email' => $email])->row_array();

        if($user) {
            $user_token = $this->db->get_where('user_token', ['token' => $token])->row_array(); 
            
            if ($user_token) {
                if(time() - $user_token['date_created'] < (60*60*24)) {
                    $this->session->set_userdata('reset_email', $email);
                    $this->changePassword();
                } else {
                    $this->db->delete('user', ['email' => $email]);
                    $this->db->delete('user_token', ['email' => $email]);
                    
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                    Reset password failed! <b>Expired token</b>.</div>');
                    redirect('auth');
                }
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                Reset password failed! <b>Wrong token</b>.</div>');
                redirect('auth');
            }
        } else {
            redirect('auth/blocked');
        }
    }



    public function changePassword()
    {
        if(!$this->session->userdata('reset_email')) {
            redirect('auth');
        }
        
        $this->form_validation->set_rules('password1', 'Password', 'trim|required|matches[password2]|min_length[8]');
        $this->form_validation->set_rules('password2', 'Repeat Password', 'trim|required|matches[password1]|min_length[8]');

        if(!$this->form_validation->run()) {
            $data['title'] = 'Forgot Password';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/change-password');
            $this->load->view('templates/auth_footer');
        } else {
            $password = password_hash($this->input->post('password1'), PASSWORD_DEFAULT);
            $email = $this->session->userdata('reset_email');

            $this->db->set('password', $password);
            $this->db->where('email', $email);
            $this->db->update('user');

            $this->session->unset_userdata('reset_email');
            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
            Password has been <b>changed</b>. Please login</div>');
            redirect('auth');
        }
    }

}
