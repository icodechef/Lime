<?php

namespace Controller;

class About extends \Controller\Front
{
    public function index()
    {
        return view('about.php');
    }
}