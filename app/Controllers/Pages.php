<?php

namespace App\Controllers;

class Pages extends BaseController
{
    public function login(): string
    {
        return view('pages/login');
    }

    public function dashboard(): string
    {
        return view('pages/dashboard');
    }

    public function inspect(): string
    {
        return view('pages/inspect');
    }

    public function query(): string
    {
        return view('pages/query');
    }

    public function test(): string
    {
        return view('pages/test');
    }
}