<?php
/**
 * Created by PhpStorm.
 * User: bisikennadi
 * Date: 8/25/14
 * Time: 12:00 PM
 */
class SignupController extends \Phalcon\Mvc\Controller
{

    public function indexAction()
    {

    }

    public function registerAction()
    {

        $user = new Users();

        $success = $user->save($this->request->getPost(), array('name','email'));

        if($success){
            echo 'Thank you, for registering!';
        } else {
            echo 'Sorry, the following problems were generated.: ';
            foreach($user->getMessage() as $message){
                echo $message->getMessage(), "<br>";
            }
        }

        $this->view->disable();

    }

}