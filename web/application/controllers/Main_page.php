<?php

use Model\Boosterpack_model;
use Model\Post_model;
use Model\User_model;
use Model\Login_model;
use Model\Comment_model;

/**
 * Created by PhpStorm.
 * User: mr.incognito
 * Date: 10.11.2018
 * Time: 21:36
 */
class Main_page extends MY_Controller
{

    public function __construct()
    {

        parent::__construct();

        if (is_prod())
        {
            die('In production it will be hard to debug! Run as development environment!');
        }
    }

    public function index()
    {
        $user = User_model::get_user();
        App::get_ci()->load->view('main_page', ['user' => User_model::preparation($user, 'default')]);
    }

    public function get_all_posts()
    {
        $posts =  Post_model::preparation_many(Post_model::get_all(), 'default');
        return $this->response_success(['posts' => $posts]);
    }

    public function get_boosterpacks()
    {
        $posts =  Boosterpack_model::preparation_many(Boosterpack_model::get_all(), 'default');
        return $this->response_success(['boosterpacks' => $posts]);
    }
    public function get_current_user()
    {
        $user = User_model::get_user();
        return $this->response_success(['user' => User_model::preparation($user, 'default')]);
    }

    public function get_post(int $post_id){

        try{
            $post = Post_model::preparation(Post_model::get_post($post_id), 'full_info');
        }
        catch (Exception $e){
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, [''], 400);
        }
        return $this->response_success(['post' => $post], 200);
    }


    public function comment(){
        
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH, ['need_auth' => true], 401);
        }
        $input = App::get_ci()->input->post();
        if (empty($input['post_id']) || empty($input['text'])) {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_WRONG_PARAMS, ['wrong_input_text' => true], 404);
        }
        $input['user_id'] = User_model::get_user()->get_id();
        $input['assign_id'] = $input['post_id'];
        $input['likes'] = 0;
        if (empty($input['assign_id'])) {
            $input['assign_id'] = 0;
        }
        if (empty($input['reply_id'])) {
            $input['reply_id'] = 0;
        } 
        $comment = Comment_model::preparation(Comment_model::create($input), 'default');
        $post = Post_model::preparation(Post_model::get_post($input['post_id']), 'full_info');

        if ($comment) { 
            return $this->response_success(array('comment' => $comment, 'post' => $post), 200);
        }else{
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, [''], 400);
        }
    }


    public function login()
    {
        $input = App::get_ci()->input->post();
        if (!empty($input['login']) && !empty($input['password'])) {
            $login_data['email'] = $input['login'];
            $login_data['password'] = $input['password'];
            $user = Login_model::login($login_data);
            $user_id = $user->get_id();
            if (!empty($user_id)) { 
                return $this->response_success(array('user' => $user_id), 200);
            }
            return $this->response_error('Incorrect login or password', ['incorrect_login_or_password' => true], 401);
        }else{
            return $this->response_error('Login of password field is empty', ['incorrect_login_or_password' => true], 401);
        }
    }
    public function logout()
    {
        Login_model::logout();
        return $this->response_success(['logout_success' => true], 200);
    }

    public function add_money(){
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH, ['need_auth' => true], 401);
        }
        $sum = floatval(App::get_ci()->input->post('sum'));
        
        try{
            $user = User_model::get_user();
            $is_add_money = $user->add_money($sum);

            if ($is_add_money) {
                $user->reload();
                return $this->response_success(array( 'user' => User_model::preparation($user, 'default')), 200);
            }else{
                return $this->response_error('Bad amount of money', ['bad_amount_of_money' => true], 400);
            }
        }
        catch (Exception $e){
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, ['bad_amount_of_money' => true], 400);
        }
    }

    public function buy_boosterpack()
    {
        // Check user is authorize
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH, ['need_auth' => true], 401);
        }

        $boosterpack_id = floatval(App::get_ci()->input->post('id'));

        try { 
            $boosterpack = Boosterpack_model::get_boosterpack($boosterpack_id);
            $user = User_model::get_user();

            if (!$user->is_loaded(TRUE) || !$boosterpack->is_loaded(TRUE)){
                return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, [''],  400);
            }
        }
        catch(RuntimeException $e) {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, [''],  400);
        } 
        catch(Exeption $e) {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, [''],  400);
        }

        if ($user->get_wallet_balance() - $boosterpack->get_price() < 0) { 
            return $this->response_error('Not enough wallets', ['not_enough_wallets' => true],  400);
        }
        
        $max_value = $boosterpack->get_bank() + ($boosterpack->get_price() - $boosterpack->get_us());

        try { 
            $available_items = $boosterpack->get_contains($max_value);
            if (count($available_items) < 1) { 
                return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, [''],  400);
            }
            $random_item = $available_items[array_rand($available_items)];
        }
        catch(RuntimeException $e) {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, [''],  400);
        } 
        catch(Exeption $e) {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, [''],  400);
        }
    
        try {
            $user->buy_likes($random_item->get_price(), $boosterpack->get_price());
            $user->reload();
        }
        catch(RuntimeException $e) {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, [''],  400);
        } 
        catch(Exeption $e) {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, [''],  400);
        }

        $new_bank = $boosterpack->get_price() - $boosterpack->get_us() - $random_item->get_price();

        if ($new_bank >= 0) {
            $boosterpack->set_bank($new_bank);
        }else{
            $boosterpack->set_bank(0);
        }

        return $this->response_success(array(   'amount' => $random_item->get_price(),
                                                'user' => User_model::preparation($user, 'default')                             
                                            ), 200);
    }


    /**
     *
     * @return object|string|void
     */
    public function like_comment(int $comment_id)
    {
        // Check user is authorize
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH, ['need_auth' => true], 401);
        }

        $user = User_model::get_user();
        if ($user->get_likes_balance() < 1) { 
            return $this->response_error('Not enough Likes', ['not_enough_likes' => true],  400);
        }
        $comment = Comment_model::get_comment($comment_id);

        $is_comment_liked = $comment->increment_likes();
        $is_user_balace_decr = $user->decrement_likes();

        if ($is_comment_liked && $is_user_balace_decr) {
            $comment->reload();
            $user->reload();
            return $this->response_success(array(   'comment' => Comment_model::preparation($comment, 'default'), 
                                                    'user' => User_model::preparation($user, 'default')), 200);
        }else{
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, '', 400);
        }
    }

    /**
     * @param int $post_id
     *
     * @return object|string|void
     */
    public function like_post(int $post_id)
    {
        // Check user is authorize
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH, ['need_auth' => true], 401);
        }

        $user = User_model::get_user();
        $post = Post_model::get_post($post_id);

        if ($user->get_likes_balance() < 1) { 
            return $this->response_error('Not enough Likes', ['not_enough_likes' => true],  400);
        }

        $is_post_liked = $post->increment_likes();
        $is_user_balace_decr = $user->decrement_likes();

        if ($is_post_liked && $is_user_balace_decr) {
            $post->reload();
            $user->reload();
            return $this->response_success(array(   'post' => Post_model::preparation($post, 'full_info'), 
                                                    'user' => User_model::preparation($user, 'default')), 200);
        }else{
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_INTERNAL_ERROR, '', 400);
        }
    }


    /**
     * @return object|string|void
     */
    public function get_boosterpack_info(int $bootserpack_info)
    {
        // Check user is authorize
        if ( ! User_model::is_logged())
        {
            return $this->response_error(System\Libraries\Core::RESPONSE_GENERIC_NEED_AUTH, ['need_auth' => true], 401);
        }


        //TODO получить содержимое бустерпак
    }
}
