<?php

namespace Model;

class Posts extends \Lime\Model
{
    protected $db;

    public function __construct()
    {
        $this->db = services('database');

        $sql = "SELECT * FROM {{posts}} ORDER BY date_add DESC";

        $sth = $this->db->query($sql);

        while($row = $sth->fetch()) {
            $this->add($row);
        }
    }

    public function getDateAdd($date)
    {
        $date = strtotime($date);
        $etime = time() - $date;
        if ($etime < 1) {
            return '刚刚';
        }

        $interval = array(
            12 * 30 * 24 * 60 * 60 => '年前 (' . date('Y-m-d', $date) . ')',
            30 * 24 * 60 * 60 => '个月前 (' . date('m-d', $date) . ')',
            7 * 24 * 60 * 60 => '周前 (' . date('m-d', $date) . ')',
            24 * 60 * 60 => '天前',
            60 * 60 => '小时前',
            60 => '分钟前',
            1 => '秒前',
        );
        foreach ($interval as $secs => $str) {
            $d = $etime / $secs;
            if ($d >= 1) {
                $r = round($d);
                return $r . $str;
            }
        };
    }

    public function getUid($uid)
    {
        $sql = "SELECT * FROM {{users}} WHERE uid=:uid";

        $sth = $this->db->query($sql, [':uid' => $uid]);

        if ($row = $sth->fetch()) {
            return $row['username'];
        }

        return '匿名用户';
    }

    public function getEditLink()
    {
        $pid = $this->row('pid');

        return url_for('post-edit', ['pid' => $pid]);
    }
}