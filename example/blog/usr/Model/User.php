<?php

namespace Model;

class User extends \Lime\Model
{
    protected $db;

    protected $logined;

    public function __construct()
    {
        $this->db = services('database');
    }

    public function login($username, $password)
    {
        $sql = "SELECT * FROM {{users}} WHERE username=:username";

        $sth = $this->db->query($sql, [
            ':username' => $username,
        ]);

        if ($row = $sth->fetch()) {
            if (md5($password) == $row['password']) {
                $this->logined = true;
                return $row['uid'];
            }
        }

        return false;
    }
}