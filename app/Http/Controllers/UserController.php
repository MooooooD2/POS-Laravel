<?php

// ---------------------------------------------------------------
// FILE: app/Http/Controllers/DashboardController.php
// ---------------------------------------------------------------
namespace App\Http\Controllers;

use App\Models\User;



class UserController extends Controller
{
    // In UserController.php
    public function all()
    {
        $users = User::select('id', 'full_name')->get();
        return response()->json(['users' => $users]);
    }
}
