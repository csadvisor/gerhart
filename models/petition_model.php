<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Petition_model extends CI_Model {

    #var $title   = '';
    #var $content = '';
    #var $date    = '';

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    
    function all()
    {
      $query = $this->db->get('petitions');
      return $query->result();
    }

    function find($id)
    {
      $query = $this->db->get_where('petitions', array('id' => $id), 1);
      $result = $query->result();
      if (empty($result)) {
        return null;
      }
      return $result[0];
    }
    #function get_last_ten_entries()
    #{
    #    $query = $this->db->get('entries', 10);
    #    return $query->result();
    #}

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
