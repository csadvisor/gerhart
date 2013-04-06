<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class App extends CI_Controller {

  function __construct()
  {
    parent::__construct();
    $this->load->model('Petition_model', '', TRUE);
    $this->load->model('User_ctx_model', '', TRUE);
    $this->load->helper(array('form', 'url'));
  }

  public function user_ctx()
  {
    $this->send($this->User_ctx_model->get());
  }

  public function notify()
  {
    $this->Petition_model->send_created_notification();
    echo 'Success';
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
      $this->_delete($id);
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

  function _delete($id)
  {
    $petition = $this->Petition_model->find($id);
    if (is_null($petition))
      $this->sendError(array('statusCode' => 404, 'reason' => 'Not found'));
    else
      $this->Petition_model->delete($id);
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
  function transcript ($id = NULL) {

    #
    # VALIDATE
    # Make sure user has correct permissions to view/upload transcript
    #

    if (!is_null($id)) {

      # TODO: Don't let someone elses advisor look at your transcript?
      #$is_viewers_advisee    = $viewing_csid == $advisor_csid;
      #if (!($is_admin or $is_viewers_advisee or $is_viewers_transcript)) {
      
      $is_viewers_transcript = $this->User_ctx_model->id() == $id;
      $is_advisor = $this->User_ctx_model->role() == 'advisor';
      $is_admin = $this->User_ctx_model->role() == 'admin';

      if (!($is_admin or $is_advisor or $is_viewers_transcript)) {
          echo 'You are unauthorized to view this transcript';
          return;
      }

    } else {
      $id = $this->User_ctx_model->id();
    }

    #
    # FILE I/O
    # Get or write transcript .pdf file
    #

    $this->load->helper('file');

    /* handle post to this URL */
    $post = $_SERVER['REQUEST_METHOD'] == 'POST';
    if ($post)
      {
        $config['upload_path'] = './system/application/static/';
        $config['allowed_types'] = 'pdf';
        $config['overwrite'] = True;
        $config['max_size'] = 10000; // 10 MB max
        $config['file_name'] = $id;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload())
          {
            $error = $this->upload->display_errors();
            header("HTTP/1.1 400 Bad request");
            echo $error;
          }
        else
          {
            # HACK: would be nice to use redirect or something here
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: http://bit.ly/cs_petitions');
          }
      }
    else
      {
        // TODO only do this on get requests
        header('Content-Type: application/pdf');
        echo read_file('./system/application/static/'.$id.'.pdf');
      }
  }
}
