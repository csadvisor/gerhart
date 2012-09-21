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
      $criteria = $this->User_ctx_model->addRoleFKey(array());
      $query = $this->db->get_where($this->TABLE_NAME, $criteria);
      return $query->result();
    }

    /*
     * find
     */
    function find($id)
    {
      $criteria = $this->User_ctx_model->addRoleFKey(array('id' => $id));
      $query = $this->db->get_where($this->TABLE_NAME, $criteria, 1);
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
      $this->db->insert($this->TABLE_NAME, $this->attributes());
      # TODO: retrieve record and send back ctime, mtime
      $this->_set('id', $this->db->insert_id());
    }

    function update()
    {
      $oldDoc = $this->find($this->_get('id'));
      if (empty($oldDoc))
        return array('statusCode' => 404, 'error' => 'Not found', 'reason' => 'Document not found');
      $errors = $this->validate($oldDoc);

      if (!empty($errors))
        return $errors;

      $criteria = $this->User_ctx_model->addRoleFKey(array('id' => $this->_get('id')));
      $this->db->where('id', $this->_get('id'));
      $this->db->update($this->TABLE_NAME, $this->attributes());
    }


    /*
     * Helpers
     */

    private function _isField($key)
    {
      return in_array($key, $this->fields);
    }

    private function _set($name, $value)
    {
      if ($this->_isField($name) && !empty($name) && !empty($value))
          $this->_attributes[$name] = $value;
    }

    function get($name) { $this->_get($name); }

    private function _get($name)
    {
      if ($this->_isField($name) && array_key_exists($name, $this->attributes()))
        {
          $attributes = $this->attributes();
          return $attributes[$name];
        }
    }

    private function validate($oldDoc) {
      // TODO: validate state transitions
    }

}
