<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_ctx_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
        $csid = getenv("WEBAUTH_USER");

        #
        # Testing with using other peoples CSIDs
        #
        
        #$csid = 'monica.lam'; # my advisor
        #$csid = 'dan.boneh'; # another advisor
        #$csid = 'twangcat'; # another MSCS student
        #$csid = 'hutchin'; # admin
        #$csid = 'stager'; # admin
        
        $query = $this->db->get_where('people', array('primary_csalias' => $csid), 1);
        $result = $query->result();
        $result = $result[0];

        $this->id = $result->id;
        $this->first_name = $result->nam_friendly;
        $this->last_name = $result->nam_last;

        $this->role = $this->_getRole($this->id);
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
        'last_name' => $this->last_name,
        'role' => $this->role
      );
    }

    function role()
    {
      return $this->role;
    }

    /*
     * id
     *
     * @description returns id for current user session
     * @todo get this from environment variable
     */
    function id()
    {
      return $this->id;
    }

    function addRoleFKey($criteria)
    {
      switch ($this->role)
      {
      case 'advisee':
        $criteria['student_id'] = $this->id();
            break;
      case 'advisor':
        $criteria['advisor_id'] = $this->id();
            break;
      case 'admin':
        // admin get all petitions
            break;
      }
      return $criteria;
    }

    private function _getRole($id)
    {
        $query = $this->db->get_where('people_relations', array('person_id' => $id));
        $result = $query->result();

        foreach ($result as $relation)
          foreach ($relation as $key => $value)
          {
            if ($key == 'relation_id')
              switch ($value)
              {
              case 10: # MSCS-ACTIVE
                return 'advisee';
              case 1: # ACADEMIC-COUNCIL
                return 'advisor';
              case 4: # FACULTY-CONSULTING
                return 'advisor';
              case 5: # FACULTY-COURTESY
                return 'advisor';
              case 6: # FACULTY-REGULAR
                return 'advisor';
              case 30: # FACULTY-VISITING
                return 'advisor';
              case 44: # LECTURER
                return 'advisor';
              case 79: # FACULTY-EMRITUS
                return 'advisor';
              case 3: # STAFF-ADMIN
                return 'admin';
              }
          }
    }
}
