<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class App extends CI_Controller {

	/**
    * Switches between all routes for petitions
	 */
	public function petitions($id = null)
	{
    switch ($_SERVER['REQUEST_METHOD'])
    {
    case 'GET':
      //echo 'GET';
      $this->_get($id);
          break;
    case 'POST':
      echo 'POST';
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

  function _get($id = null) {
    if (isset($id)) { return $this->_get_id($id); }

    echo '/petitions';
  }

  function _get_id($id) {
    echo '/petitions/'.$id;
  }
}
