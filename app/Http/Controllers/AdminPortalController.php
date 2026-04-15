<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminPortalController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.portal');
    }
}
