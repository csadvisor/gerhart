<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_ctx_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
    $csid = getenv("WEBAUTH_USER");

    #
    # Testing with using other peoples CSIDs
    #

    #$csid = 'mcumings';
    #$csid = 'federico.barbagli';
    #$csid = 'monica.lam'; # my advisor
    #$csid = 'dan.boneh'; # another advisor
    #$csid = 'twangcat'; # another MSCS student
    #$csid = 'stager'; # admin
    #$csid = 'miles'; # should not be involved
    #$csid = 'nicole';
    #$csid = 'roycecy';
    #$csid = 'coopers';
    #$csid = 'crknight';
    #$csid = 'mrg';
    #$csid = 'plotkin';
    #$csid = 'sjovanov';
    #$csid = 'hanlee';
    #$csid = 'schneibe';
    #$csid = 'paulo';

    $this->csid = $csid;

    # look up person_id
    $query = $this->db->get_where('csaliases', array('cs_name' => $csid), 1);
    $result = $query->result();
    $result = $result[0];

    # look up person entry
    $query = $this->db->get_where('people', array('id' => $result->person_id), 1);
    $result = $query->result();
    $result = $result[0];

    $this->id = intval($result->id);
    $this->first_name = $result->nam_friendly;
    $this->last_name = $result->nam_last;
    $this->email_address = $result->primary_csalias . '@cs.stanford.edu';

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
    $result = array(
      'id' => $this->id,
      'first_name' => $this->first_name,
      'last_name' => $this->last_name,
      'csid' => $this->csid
    );

    if (!is_null($this->role))
      $result['role'] = $this->role;

    if ($this->transcriptUploaded())
      $result['transcript'] = true; 

    if ($this->role == 'advisee')
      $result['advisor_id'] = $this->advisorId();

    return $result;
  }

  function fullName()
  {
    return $this->first_name . ' ' . $this->last_name;
  }

  function role()
  {
    return $this->role;
  }

  function csid()
  {
    return $this->csid;
  }

  function transcriptUploaded()
  {
    return file_exists('./system/application/static/'.$this->id.'.pdf');
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

    function addRoleFKey($criteria, $prefix = '')
    {
      switch ($this->role)
      {
      case 'advisee':
        $criteria[$prefix . 'student_id'] = $this->id();
            break;
      case 'advisor':
        $criteria[$prefix . 'advisor_id'] = $this->id();
            break;
      case 'admin':
            /* no filter required - get all petitions */
            break;
      }
      return $criteria;
    }

    function adviseeId()
    {
    }

  // TODO this is copy pasted from petitions_model
  function emailForId($id)
  {
    $query = $this->db->get_where('csaliases', array('person_id' => $id), 6);
    $result = $query->result();
    $result = $result[count($result) - 1];
    $email = $result->cs_name . '@cs.stanford.edu';
    return $email;
  }

  function parseEmail($field, $query)
  {
    if ($field == 'advisor_id')
      return $this->emailForId($query->advisor_id);
    if ($field == 'student_id')
      return $this->emailForId($query->student_id);
  }

  /*
   *  getEmails
   *  This should only be called by advisee or advisor to send notifications
   */
  function getEmails()
  {
    if ($this->role() != 'advisee' && $this->role() != 'advisor') return null;

    $this->db->where('advisor_id', $this->id());
    $this->db->or_where('student_id', $this->id());
    $petitions = $this->db->get('petitions', 1)->result();
    $petition = $petitions[0];

    return array(
      'advisee_email' => $this->parseEmail('student_id', $petition),
      'advisor_email' => $this->parseEmail('advisor_id', $petition),
    );
  }


    function advisorId()
    {
      if ($this->role() != 'advisee') return null;

      $query = $this->db->get_where('mscsactive', array('person_id' => $this->id()), 1);
      $result = $query->result();
      return intval($result[0]->advisor_id);
    }

    private function _getRole($id)
    {
      /*
       * special cases
       */
      if ($id == 11354) { /* federico.barbagli@cs.stanford.edu (legacy advisor) */
        return 'advisor';
      }
      if ($id == 13725 || $id == 14228) { /* Jack Dubie */
        return 'admin';
      }

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
        return null;
    }
}
