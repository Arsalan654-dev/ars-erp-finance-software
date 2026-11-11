<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with(['head', 'members'])->paginate(15);
        $users = User::where('is_active', true)->orderBy('name')->get();
        return view('erp.departments.index', compact('departments', 'users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:departments,name'],
            'slug' => ['required', 'string', 'max:50', 'unique:departments,slug'],
            'head_id' => ['nullable', 'exists:users,id'],
        ]);

        $data['is_active'] = true;
        Department::create($data);

        return back()->with('success', 'Department created.');
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'head_id' => ['nullable', 'exists:users,id'],
        ]);

        $department->update($data);

        return back()->with('success', 'Department updated.');
    }

    public function addMember(Request $request, Department $department)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role_in_department' => ['required', 'in:head,member,viewer'],
        ]);

        $department->members()->syncWithoutDetaching([$data['user_id'] => ['role_in_department' => $data['role_in_department']]]);

        return back()->with('success', 'Member added.');
    }

    public function removeMember(Department $department, User $user)
    {
        $department->members()->detach($user->id);
        return back()->with('success', 'Member removed.');
    }
}
