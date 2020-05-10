<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Menu_model extends CI_Model
{
    public function getAllMenu()
    {
        return $this->db->get('user_menu')->result_array(); // karena banyak jd pake result_array()
    }
    
    public function getAllSubmenu()
    {
        $query = "SELECT `user_sub_menu`.*, `user_menu`.`menu`
                FROM `user_sub_menu` JOIN `user_menu`
                ON `user_sub_menu`.`menu_id` = `user_menu`.`id`
            ";
    
        return $this->db->query($query)->result_array();
    }

    public function addMenu($menu)
    {
        $this->db->insert('user_menu', ['menu' => $menu]);
    }

    public function deleteSubmenu($id)
    {
        $this->db->delete('user_sub_menu', ['id' => $id]);
    }

    public function deleteMenu($id)
    {
        $this->db->delete('user_menu', ['id' => $id]);
    }

    public function editMenu($id)
    {
        $data = [
            "menu" => $this->input->post('menu', true) // "true" = htmlspecialchars($_POST['']);
        ];
        $this->db->update('user_menu', $data, ['id' => $id]);
    }
    
    public function editSubmenu($id)
    {
        $data = [
            "title" => $this->input->post('title', true),
            "menu_id" => $this->input->post('menu_id', true),
            "url" => $this->input->post('url', true),
            "icon" => $this->input->post('icon', true),
        ];
        $this->db->update('user_sub_menu', $data, ['id' => $id]);
    }
}

