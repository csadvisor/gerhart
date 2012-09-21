<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class App extends CI_Controller {

  function __construct()
  {
    parent::__construct();
    $this->load->model('Petition_model', '', TRUE);
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
      echo 'PUT';
          break;
    case 'DELETE':
      echo 'DELETE';
          break;
    default:
      echo 'ELSE';
    }
	}

  function _get($id = null)
  {
    if (isset($id)) { return $this->_get_id($id); }

    $this->send($this->Petition_model->all());
  }

  function _get_id($id)
  {
    $this->send($this->Petition_model->find($id));
  }

  function _post()
  {
    $petition = $this->parsePetition();
    $petition->create();
    $this->send($petition->attributes());
  }


  /*
   * Send
   */
  function send($response)
  {
    if (empty($response))
      {
        show_404();
        #echo '404';
      }
    else
      {
        echo json_encode($response);
      }
  }

  function parsePetition()
  {
    $data = file_get_contents('php://input');
    $data = json_decode($data, true);
    $petition = new $this->Petition_model;
    $petition->loadAttributes($data);
    return $petition;
  }
}
