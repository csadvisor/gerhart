<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_ctx_model extends CI_Model {

    var $id = 13725;
    var $first_name = 'Joe';
    var $last_name  = 'Schmoe';

    function __construct()
    {
        parent::__construct();
    }
    
    /*
     * get
     *
     * @description get user information
     * @todo look this up from people table
     */
    function get()
    {
      return array(
        'id' => $this->id,
        'first_name' => $this->first_name,
        'last_name' => $this->last_name
      );
    }

    /*
     * id
     *
     * @description returns id for current user session
     * @todo get this from environment variable
     */
    function id()
    {
      #$query = $this->db->get_where('users', array('id' => $this->id), 1);
      #$result = $query->result();
      #if (empty($result)) {
      #  return null;
      #}
      #return $result[0];

      return $this->id;
    }

}
