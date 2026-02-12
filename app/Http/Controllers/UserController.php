<?php

namespace App\Http\Controllers;

use Aksa\Http\Controller;
use Aksa\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return $this->view('welcome');
    }
}
