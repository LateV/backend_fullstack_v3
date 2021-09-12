<?php

namespace Model;

use App;
use Exception;
use System\Core\CI_Model;

class Login_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    public static function logout()
    {
        App::get_ci()->session->unset_userdata('id');
    }

    /**
     * @return User_model
     * @throws Exception
     */
    public static function login($login_data): User_model
    {
        $user = User_model::find_user_by_email($login_data['email']);
        $user_id = $user->get_id();
        if (!empty($user_id) && $login_data['password'] == $user->get_password()) {
            self::start_session($user->get_id());
        }else{
            return new User_model;
        }
        return $user;
    }

    public static function start_session(int $user_id)
    {
        if (empty($user_id))
        {
            throw new Exception('No id provided!');
        }
        App::get_ci()->session->set_userdata('id', $user_id);

    }
}
