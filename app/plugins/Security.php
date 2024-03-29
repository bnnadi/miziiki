<?php
/**
 * Created by PhpStorm.
 * User: bisikennadi
 * Date: 8/25/14
 * Time: 12:49 PM
 */

use Phalcon\Events\Event,
    Phalcon\Mvc\User\Plugin,
    Phalcon\Mvc\Dispatcher,
    Phalcon\Acl;

class Security extends Plugin
{

    public function __construct($dependencyInjector)
    {
        $this->_dependencyInjector = $dependencyInjector;
    }

    public function getAcl()
    {
        if (!isset($this->persistent->acl)) {

            $acl = new Phalcon\Acl\Adapter\Memory();

            $acl->setDefaultAction(Phalcon\Acl::DENY);

            //Register roles
            $roles = array(
                'users'  => new Phalcon\Acl\Role('Users'),
                'guests' => new Phalcon\Acl\Role('Guests')
            );
            foreach ($roles as $role) {
                $acl->addRole($role);
            }

            //Private area resources
            $privateResources = array(
                'companies'    => array('index', 'search', 'new', 'edit', 'save', 'create', 'delete'),
                'products'     => array('index', 'search', 'new', 'edit', 'save', 'create', 'delete'),
                'producttypes' => array('index', 'search', 'new', 'edit', 'save', 'create', 'delete'),
                'invoices'     => array('index', 'profile')
            );
            foreach ($privateResources as $resource => $actions) {
                $acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
            }

            //Public area resources
            $publicResources = array(
                'index'   => array('index'),
                'about'   => array('index'),
                'session' => array('index', 'register', 'start', 'end'),
                'contact' => array('index', 'send')
            );
            foreach ($publicResources as $resource => $actions) {
                $acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
            }

            //Grant access to public areas to both users and guests
            foreach ($roles as $role) {
                foreach ($publicResources as $resource => $actions) {
                    $acl->allow($role->getName(), $resource, '*');
                }
            }

            //Grant acess to private area to role Users
            foreach ($privateResources as $resource => $actions) {
                foreach ($actions as $action){
                    $acl->allow('Users', $resource, $action);
                }
            }

            //The acl is stored in session, APC would be useful here too
            $this->persistent->acl = $acl;
        }

        return $this->persistent->acl;
    }

    public function beforeDispatch(Event $event, Dispatcher $dispatcher)
    {
        //Check whether the "auth" variable exists in session to define the active role
        $auth = $this->session->get('auth');
        if (!$auth) {
            $role = 'Guests';
        } else {
            $role = 'Users';
        }

        //Take the active controller/action from the dispatcher
        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();

        //Obtain the ACL list
        $acl = $this->getAcl();

        //Check if the Role have access to the controller (resource)
        $allowed = $acl->isAllowed($role, $controller, $action);
        if ($allowed != Acl::ALLOW) {

            //If he doesn't have access forward him to the index controller
            $this->flash->error("You don't have access to this module");
            $dispatcher->forward(
                array(
                    'controller' => 'index',
                    'action' => 'index'
                )
            );

            //Returning "false" we tell to the dispatcher to stop the current operation
            return false;
        }
    }

}