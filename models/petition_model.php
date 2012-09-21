<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Petition_model extends CI_Model {

    #var $title   = '';
    #var $content = '';
    #var $date    = '';

    function __construct()
    {
        $this->load->model('User_ctx_model', '', TRUE);
        parent::__construct();
    }
    
    function all()
    {
      $query = $this->db->get_where('petitions', array(
        'student_id' => $this->User_ctx_model->id()
      ));
      return $query->result();
    }

    function find($id)
    {
      $query = $this->db->get_where('petitions', array(
        'id' => $id,
        'student_id' => $this->User_ctx_model->id()

      ), 1);
      $result = $query->result();
      if (empty($result)) {
        return null;
      }
      return $result[0];
    }

    #function insert_entry()
    #{
    #    $this->title   = $_POST['title']; // please read the below note
    #    $this->content = $_POST['content'];
    #    $this->date    = time();

    #    $this->db->insert('entries', $this);
    #}

    #function update_entry()
    #{
    #    $this->title   = $_POST['title'];
    #    $this->content = $_POST['content'];
    #    $this->date    = time();

    #    $this->db->update('entries', $this, array('id' => $_POST['id']));
    #}

}
