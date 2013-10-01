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

    if (!$this->isTest)
      {
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
            $this->sendApprovedNotification();

            break;
          case 'rejected':
            if ($oldState != 'pending' && $oldState != 'approved')
              return $this->valError('Must go from {pending,approved} => rejected');
            if ($this->User_ctx_model->role() != 'advisor') # someone else's advisor could do this
              return $this->valError('Only admins can mark a petition as processed');

            $this->sendRejectedNotification();

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

  function sendCreatedNotification() {
    $subject = 'Your advisee just created an MSCS waiver request';
    $roles = array('advisor', 'advisee');
    $message = array(
      'Dear Advisor,',
      '',
      'Your advisee just created a petition. '
      . '"Approve" or "Decline" their request here,'
      . ' http://j.mp/cs_petitions. If you want to talk with your advisee'
      . ' in person before making a decision then "Reply All" to this'
      . ' email and tell them your availability.'
    );

    $this->sendNotification($roles, $subject, $message);
  }

  function sendApprovedNotification()
  {
    $roles = array('admin', 'advisee');
    $subject = 'Advisor has approved your MSCS waiver request';
    $message = array(
      'Dear Student,',
      '',
      'Your advisor has approved your MSCS waiver request. Claire Stager'
      . ' (stager@cs.stanford.edu) will print out your waiver and keep it on'
      . ' file until you graduate.'
    );

    $this->sendNotification($roles, $subject, $message);
  }

  function sendRejectedNotification()
  {
    $roles = array('advisee');
    $subject = 'Your MSCS waiver request has been rejected by your advisor';
    $body = array(
      'Dear student,',
      '',
      'Your MSCS waiver has been rejected. If you feel there was a mistake email'
      . ' your advisor.',
    );

    $this->sendNotification($roles, $subject, $body);
  }

  function emailForId($id)
  {
    $query = $this->db->get_where('csaliases', array('person_id' => $id), 6);
    $result = $query->result();
    $result = $result[count($result) - 1];
    $email = $result->cs_name . '@cs.stanford.edu';
    return $email;
  }

  function getEmails()
  {
    if ($this->isTest)
      {
        return array(
          'advisee_email' => 'advisee@cs.stanford.edu',
          'advisor_email' => 'advisor@cs.stanford.edu',
        );
      }
    else
      {
        if ($this->_get('student_id') && $this->_get('advisor_id'))
          {
            return array(
              'advisee_email' => $this->emailForId($this->_get('student_id')),
              'advisor_email' => $this->emailForId($this->_get('advisor_id')),
            );
          }
        else
          return $this->User_ctx_model->getEmails();
      }
  }

  function sendNotification($roles, $subject, $body)
  {
    /**
     * Fill to array
     */
    $to = array('petitions@cs.stanford.edu');
    $emails = $this->getEmails();
    foreach ($roles as $role) {
      switch ($role) {
        case 'admin':
          array_push($to, 'stager@cs.stanford.edu');
          break;
        case 'advisee':
          array_push($to, $emails['advisee_email']);
          break;
        case 'advisor':
          array_push($to, $emails['advisor_email']);
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
      mail($to, $subject, $message);
    }
  }
}
