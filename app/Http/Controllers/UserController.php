<?php

namespace App\Http\Controllers;

use MiniLaravel\Http\Controller;
use MiniLaravel\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return $this->view('welcome');
    }
}
