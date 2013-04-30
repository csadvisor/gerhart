<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

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
    'transcript',
    'created_on', # HACK using this field for date_approved
    'date_approved',
    'last_modified',
  );

  var $_attributes = array();

  function __construct()
  {
    /* set isTest */
    $this->isTest = getenv('TEST') == 'localtest';

    if (!$this->isTest) {
      $this->load->model('User_ctx_model', '', TRUE);
      parent::__construct();
    }
  }
  
  /*
   * all
   */
  function all()
  {
    /*
     * double join example:
     *
     * SELECT t.PhoneNumber1, t.PhoneNumber2, 
     *      t2.SomeOtherFieldForPhone1, t3.someOtherFieldForPhone2
     *      FROM Table1 t
     *      JOIN Table2 t1 ON t1.PhoneNumber = t.PhoneNumber1
     *      JOIN Table2 t2 ON t2.PhoneNumber = t.PhoneNumber2
     */
    
    /*
     * Student fields
     */
    $s_fields = array(
      't1.nam_last as s_last',
      't1.nam_friendly as s_first',
      't1.primary_csalias as s_alias',
      'm.ugrad_school as s_school',
    );

    /*
     * Advisor fields
     */
    $a_fields = array(
      't2.nam_last as a_last',
      't2.nam_friendly as a_first',
      't2.primary_csalias as a_alias',
    );

    /*
     * concat fields into comma separated array
     */
    $s_fields = implode(', ', $s_fields);
    $a_fields = implode(', ', $a_fields);

    /*
     * Grab fields from petitions and advisee and advisor
     */
    $this->db->select('t.*, ' . $a_fields . ', ' . $s_fields); 
    $this->db->join('people t1', 't1.id = t.student_id'); 
    $this->db->join('people t2', 't2.id = t.advisor_id'); 
    $this->db->join('mscsactive m', 'm.person_id = t.student_id');

    /*
     * sort by student last name
     */
    $this->db->order_by('s_last');
    
    /*
     * select records relevant to user 
     */
    $criteria = $this->User_ctx_model->addRoleFKey(array(), 't.'); 
    $query = $this->db->get_where($this->TABLE_NAME . ' t', $criteria); 
    
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

  /*
   * delete
   */
  function delete($id)
  {
    $this->db->delete($this->TABLE_NAME, array('id' => $id)); 
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

  function tstamp()
  {
    return date('Y-m-d G:i:s');
  }

  function create()
  {
    $errors = $this->validate();
    if (!empty($errors))
      return $errors;

    # HACK using this field for approved date
    #$this->_set('created_on', $this->tstamp());
    $this->_set('last_modified', $this->tstamp());

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

    $this->_set('last_modified', $this->tstamp());

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
    return null;
  }

  function valError($reason)
  {
    return array(
      'statusCode'  => 403,
      'error'       => 'Forbidden',
      'reason'      => $reason
    );
  }

  private function validate($oldDoc = null)
  {

    $curState = $this->_get('state');

    /*
     * create
     */

    if (empty($oldDoc))
      {
        if ($curState != 'pending')
          return $this->valError('New petitions must start as pending');

        if ($this->_get('student_id') != $this->User_ctx_model->id())
          return $this->valError('You must create a petiton for yourself');

        if ($this->_get('advisor_id') != $this->User_ctx_model->advisorId())
          return $this->valError('You must create a petiton the correct advisor');

        return;
      }

    /*
     * update
     */

    else
      {
        $oldState = $oldDoc->state;
        $newState = $this->_get('state');

        switch ($newState)
        {
          case 'pending':
            if ($oldState != 'pending')
              return $this->valError('Can only edit a pending petition');
            if ($this->User_ctx_model->role() != 'advisee') # someone else's advisor could do this
              return $this->valError('Only advisee edit pending petitions');
            break;
          case 'approved':
            if ($oldState != 'pending' && $oldState != 'rejected')
              return $this->valError('Must go from {pending,rejected} => processed');
            if ($this->User_ctx_model->role() != 'advisor') # someone else's advisor could do this
              return $this->valError('Only admins can mark a petition as processed');

            # HACK this should be it's own field
            $this->_set('created_on', $this->tstamp());
            $this->send_approved_notification();

            break;
          case 'rejected':
            if ($oldState != 'pending' && $oldState != 'approved')
              return $this->valError('Must go from {pending,approved} => rejected');
            if ($this->User_ctx_model->role() != 'advisor') # someone else's advisor could do this
              return $this->valError('Only admins can mark a petition as processed');

            $this->send_rejected_notification();

            break;
          case 'processed':
            if ($oldState != 'approved')
              return $this->valError('Must go from approved => processed');
            if ($this->User_ctx_model->role() != 'admin')
              return $this->valError('Only admins can mark a petition as processed');
            break;
        }
      }
  }

  function is_test()
  {
    if ($this->User_ctx_model->csid() == 'jdubie') return true;
    return false;
  }

  function send_created_notification() {
    if ($this->is_test()) return;

    $advisorId = $this->User_ctx_model->advisorId();
    $query = $this->db->get_where('people', array('id' => $advisorId), 1);
    $result = $query->result();
    $result = $result[0];

    $to = implode(', ', array(
      $result->primary_csalias . '@cs.stanford.edu',
      $this->User_ctx_model->email_address,
      'advisor@cs.stanford.edu',
    ));

    $studentName = $this->User_ctx_model->fullName();
    $subject = 'Your advisee, ' . $studentName . ', just created an MSCS waiver request';

    /*
     * Create message and word wrap to 70 characters per line
     */
    $message = implode('', array(
      "Advisor " . $result->nam_last . ",",
      "\r\n\r\n",
      "Your advisee, " . $studentName . ", just created a petition. ",
      "\"Approve\" or \"Decline\" their request here, ",
      "http://j.mp/cs_petitions. If you want to talk with your advisee ",
      "in person before making a decision then \"Reply All\" to this ",
      "email and tell them your availability.",
      "\r\n\r\n",
      "Sincerely,\r\n",
      "MSCS Petitions Robot\r\n",
      "\r\n",
      "Questions, bugs, or feedback? Email petitions@cs.stanford.edu\r\n",
    ));
    $message = wordwrap($message, 70, "\r\n");
   
    /*
     * Actually send email.
     * TODO: sends as current linux user `apache` - could be better
     */
    mail($to, $subject, $message);
  }

  function send_approved_notification()
  {
    if ($this->is_test()) return;

    $advisorId = $this->User_ctx_model->advisorId();
    $query = $this->db->get_where('people', array('id' => $advisorId), 1);
    $result = $query->result();
    $result = $result[0];

    $to = implode(', ', array(
      'stager@cs.stanford.edu',                      # Claire Stager
      $result->primary_csalias . '@cs.stanford.edu', # student
      'advisor@cs.stanford.edu',                     # course advisor
      # This is a lot of emails for advisors to get
      # $this->User_ctx_model->email_address; # notify advisor
    ));

    $studentName = $this->User_ctx_model->fullName();
    $subject = 'Advisor ' . $result->nam_last . ' has APPROVED your MSCS waiver request (eom)';
    
    /*
     * Word wrap
     */
    $message = wordwrap('', 70, "\r\n");

    // Send
    mail($to, $subject, $message);
  }

  function send_rejected_notification()
  {

    #$advisorId = $this->User_ctx_model->advisorId();
    #$query = $this->db->get_where('people', array('id' => $advisorId), 1);
    #$result = $query->result();
    #$result = $result[0];

    #$to = '';
    #$to = $to . $this->User_ctx_model->email_address; # notify student
    #$to = $to . ', advisor@cs.stanford.edu'; # notify course advisor
    # too many emails to advisor
    #$to = $to . ', ' . $result->primary_csalias . '@cs.stanford.edu'; # notify advisor
    
    $roles = array('advisee');
    $subject = 'Your MSCS waiver request has been rejected';
    $body = array(
      'Dear student,',
      "\r\n\r\n",
      'If you feel there was a mistake email your advisor',

    );
    $this->sendNotification($roles, $subject, $body);

    // In case any of our lines are larger than 70 characters, we should use wordwrap()
    $message = wordwrap('', 70, "\r\n");

    // Send
    mail($to, $subject, $message);
  }

  /*
   * getAdviseeEmail
   */
  function getAdviseeEmail()
  {
    if ($this->isTest) return 'advisee@cs.stanford.edu';

    switch ($this->User_ctx_model->role) {
      case 'advisor':
      case 'advisee':
        return $this->User_ctx_model->email_address;
      case 'admin':
        throw new Exception('Admin does not have scope of an advisor');
        break;
    }
    throw new Exception('Unrecognized role');
  }

  /*
   * getAdvisorEmail
   */
  function getAdvisorEmail()
  {
    if ($this->isTest) return 'advisor@cs.stanford.edu';

    switch ($this->User_ctx_model->role) {
      case 'advisor':
        return $this->User_ctx_model->email_address;
      case 'advisee':
        $advisorId = $this->User_ctx_model->advisorId();
        $query = $this->db->get_where('people', array('id' => $advisorId), 1);
        $result = $query->result();
        $result = $result[0];
        return $result->primary_csalias . '@cs.stanford.edu';
      case 'admin':
        throw new Exception('Admin does not have scope of an advisor');
        break;
    }
    throw new Exception('Unrecognized role');
  }

  function sendNotification($roles, $subject, $body)
  {
    /**
     * Fill to array
     */
    $to = array('petitions@cs.stanford.edu');
    foreach ($roles as $role) {
      switch ($role) {
        case 'admin':
          array_push($to, 'stager@cs.stanford.edu');
          break;
        case 'advisee':
          array_push($to, $this->getAdviseeEmail());
          break;
        case 'advisor':
          array_push($to, $this->getAdvisorEmail());
          break;
      }
    }
    $to = implode(', ', $to); // join array on ', '

    /*
     * Add footer and word wrap message
     */
    $footer = array(
      "",
      "Sincerely,",
      "MSCS Petitions Robot",
      "",
      "Questions, bugs, or feedback? Email petitions@cs.stanford.edu",
    );
    $body = array_merge($body, $footer);
    $body = implode("\r\n", $body);
    $message = wordwrap($body, 70, "\r\n");
   
    /*
     * Actually send email.
     * TODO: sends as current linux user `apache` - could be better
     */
    $this->sendEmail($to, $subject, $message);
  }

  /**
   * sendEmail
   */
  function sendEmail($to, $subject, $message)
  {
    if ($this->isTest) {
      echo "TO: $to\n";
      echo "SUBJECT: $subject\n";
      echo "MESSAGE:\n$message\n";
    } else {
      send($to, $subject, $message);
    }
  }
}
