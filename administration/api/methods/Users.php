<?php

namespace Api;
class Users
{

    private $users;

    function __construct()
    {
        $this->users = \ClassesOperations::autoLoadClass('\Controller\Users', '/controllers/Users.php');
    }

    public function get($id)
    {
        if ($id) {
            $this->getUserById($id);
        } else {
            $this->getUsers();
        }
    }

    public function put()
    {
        $this->users->createUser();
    }

    public function delete($id)
    {
        $this->users->deleteUser($id);
    }

    public function set($id)
    {
        $this->users->setUserData($id);
    }

    private function getUserById($id)
    {
        $this->users->getUserData($id);
    }

    private function getUsers()
    {
        $this->users->getAllUsers();
    }
}