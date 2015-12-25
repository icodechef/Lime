<?php

namespace Model;

class Post extends \Lime\Model
{
    protected $db;

    public function __construct()
    {
        $this->db = services('database');
    }

    public function add($title, $content)
    {
        $sql = "INSERT INTO {{posts}} (title,content,uid,date_add) VALUES (:title,:content,:uid,NOW())";

        $sth = $this->db->query($sql, [
            ':title' => $title,
            ':content' => $content,
            ':uid' => $_SESSION['uid'],
        ]);

        if ($sth->rowCount()) {
            return $this->db->lastInsertId();
        } else {
            return false;
        }
    }

    public function get($pid)
    {
        $sql = "SELECT * FROM {{posts}} p LEFT JOIN {{users}} u ON (p.uid=u.uid) WHERE pid=:pid";

        $sth = $this->db->query($sql, [
            ':pid' => $pid,
        ]);

        if ($row = $sth->fetch()) {
            return $row;
        } else {
            return false;
        }
    }

    public function update($pid, $title, $content)
    {
        $sql = "UPDATE {{posts}} SET 
                title=:title,
                content=:content
                WHERE pid=:pid";

        $sth = $this->db->query($sql, [
            ':title' => $title,
            ':content' => $content,
            ':pid' => $pid,
        ]);
    }
}