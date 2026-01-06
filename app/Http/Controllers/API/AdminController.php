<?php

namespace App\Http\Controllers\API;

use App\Models\Users;
use App\Models\AdminLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function suspendUser(Request $request, Users $user){
        $request -> validate([
            'note' => 'required | string | max:255',
            'days' => 'required | integer | min:1',
        ]);
        $user -> update([
            'is_suspended' => true,
            'suspension_note' => $request -> note,
            'suspended_until' => now()->addDays($request -> days),
        ]);
        AdminLog::create([
            'admin_id' => $request -> user() -> id,
            'action' => 'suspend',
            'details'=> "Suspended user {$user -> username} for {$request -> days} days, note: {$request -> note}",
        ]);
        return response()->json(['message' => 'User suspended successfully'], 200);
    }
    public function unsuspendedUser(Request $request, Users $user){
        $user->update([
            'is_suspended' => false,
            'suspension_note' => null,
            'suspended_until' => null,
        ]);
        AdminLog::create([
            'admin_id' => $request -> user() -> id,
            'action' => 'unsuspend',
            'details'=> "Unsuspended user {$user -> username}",
        ]);
        return response()->json(['message' => 'User unsuspended successfully'], 200);
    }
    public function getLogs(Request $request){
        $logs = AdminLog::with('admin')->latest()->paginate(20);
        return response()->json($logs, 200);
    }
}
