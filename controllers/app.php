<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class App extends CI_Controller {

  function __construct()
  {
    parent::__construct();
    $this->load->model('Petition_model', '', TRUE);
    $this->load->model('User_ctx_model', '', TRUE);
  }

  public function user_ctx()
  {
    $this->send($this->User_ctx_model->get());
  }

	public function petitions($id = null)
	{
    switch ($_SERVER['REQUEST_METHOD'])
    {
    case 'GET':
      $this->_get($id);
          break;
    case 'POST':
      $this->_post();
          break;
    case 'PUT':
      $this->_put_id($id);
          break;
    case 'DELETE':
      echo 'DELETE is not yet implemented';
          break;
    default:
      echo 'HTTP method not supported';
    }
	}

  function _get($id = null)
  {
    if (isset($id)) { return $this->_get_id($id); }

    $this->send($this->Petition_model->all());
  }

  function _get_id($id)
  {
    $petition = $this->Petition_model->find($id);
    if (is_null($petition))
      $this->sendError(array('statusCode' => 404, 'reason' => 'Not found'));
    else
      $this->send($this->Petition_model->find($id));
  }

  function _post()
  {
    $petition = $this->parsePetition();
    $error = $petition->create();
    if (!is_null($error))
      $this->sendError($error);
    else
      $this->send($petition->attributes());
  }

  function _put_id($id)
  {
    $petition = $this->parsePetition();

    if ($petition->get('id') == $id)
      {
        $error = array('statusCode' => 400, 'error' => 'Bad request', 'reason' => 'Body id doesnt make url id');
        return $this->sendError($error);
      }

    $error = $petition->update();
    if (!empty($error))
      return $this->sendError($error);

    $this->send($petition->attributes());
  }

  /*
   * Send
   */
  function send($response)
  {
    echo json_encode($response);
  }
  
  function sendError($error)
  {
    # TODO: add Status Code
    header("HTTP/1.1 ".$error['statusCode']." ".$error['reason']);
    $this->send($error);
  }

  function parsePetition()
  {
    $data = file_get_contents('php://input');
    $data = json_decode($data, true);
    $petition = new $this->Petition_model;
    $petition->loadAttributes($data);
    return $petition;
  }

  /* render transcript */
  function transcript ($csid = NULL) {
    $this->load->helper('file');

    if (!is_null($csid)) {

      # TODO: Don't let someone elses advisor look at your transcript?
      #$is_viewers_advisee    = $viewing_csid == $advisor_csid;
      #if (!($is_admin or $is_viewers_advisee or $is_viewers_transcript)) {
      
      $is_viewers_transcript = $this->User_ctx_model->csid() == $csid;
      $is_advisor = $this->User_ctx_model->role() == 'advisor';
      $is_admin = $this->User_ctx_model->role() == 'admin';

      if (!($is_admin or $is_advisor or $is_viewers_transcript)) {
          echo 'You are unauthorized to view this transcript';
          return;
      }

    } else {
      $csid = $this->User_ctx_model->csid();
    }

    header('Content-Type: application/pdf');
    echo read_file('./system/application/static/'.$csid.'.pdf');
  }
}
