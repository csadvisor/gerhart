<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Petition_model extends CI_Model {

    var $TABLE_NAME = 'petitions';

    var $fields = array(
      'id',
      'student_id',
      'advisor_id',
      'state',
      'stanford_description',
      'stanford_syllabus',
      'stanford_textbooks',
      'transfer_description',
      'transfer_syllabus',
      'transfer_textbooks',
      'course_number',
      'created_on',
      'last_modified',
      'transcript',
    );

    var $_attributes = array();

    function __construct()
    {
        $this->load->model('User_ctx_model', '', TRUE);
        parent::__construct();
    }
    
    /*
     * all
     */
    function all()
    {
      $query = $this->db->get_where($this->TABLE_NAME, array(
        'student_id' => $this->User_ctx_model->id()
      ));
      return $query->result();
    }

    /*
     * find
     */
    function find($id)
    {
      $query = $this->db->get_where($this->TABLE_NAME, array(
        'id' => $id,
        'student_id' => $this->User_ctx_model->id()

      ), 1);
      $result = $query->result();
      if (empty($result)) {
        return null;
      }
      return $result[0];
    }


    function loadAttributes($data)
    {
      foreach ($this->fields as $attribute)
      {
        if (array_key_exists($attribute, $data))
          $this->_set($attribute, $data[$attribute]);
      }
    }

    function attributes()
    {
      return $this->_attributes;
    }

    function create()
    {
      $this->_assignDefaults();
      $this->db->insert($this->TABLE_NAME, $this->attributes());
      # TODO: retrieve record and send back ctime, mtime
      $this->_set('id', $this->db->insert_id());
    }

    function update()
    {
      // todo validate state transitions
      $this->db->update($this->TABLE_NAME, $this->attributes, array(
        'id' => $this->User_ctx_model->id()
      ));
    }


    /*
     * Helpers
     */

    private function _isField($key)
    {
      return in_array($key, $this->fields);
    }

    private function _assignDefaults()
    {
      $this->loadAttributes(array(
        'student_id' => $this->User_ctx_model->id(),
        'state' => 'pending'
      ));
    }

    private function _set($name, $value)
    {
      if ($this->_isField($name) && !empty($name) && !empty($value))
          $this->_attributes[$name] = $value;
    }

    private function _get($name)
    {
      if ($this->_isField($name) && array_key_exists($this->attributes(), $name))
        {
          $attributes = $this->attributes();
          return $attributes[$name];
        }
    }

    #function update_entry()
    #{
    #    $this->title   = $_POST['title'];
    #    $this->content = $_POST['content'];
    #    $this->date    = time();

    #    $this->db->update('entries', $this, array('id' => $_POST['id']));
    #}
    

}
