<?php

namespace App\Http\Controllers;

use App\Models\Directorate;
use Illuminate\Http\Request;

class DirectorateController extends Controller
{
    public function index()
    {
        $directorates = Directorate::orderBy('name')->paginate(15);

        return view('directorates.index', compact('directorates'));
    }

    public function create()
    {
        return view('directorates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:directorates,name'],
            'code' => ['required', 'string', 'max:20', 'unique:directorates,code'],
            'type' => ['required', 'string', 'in:Directorate,CG Secretary'],
        ]);

        Directorate::create($validated);

        return redirect()->route('directorates.index')->with('success', 'Directorate created successfully.');
    }

    public function edit(Directorate $directorate)
    {
        return view('directorates.edit', compact('directorate'));
    }

    public function update(Request $request, Directorate $directorate)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:directorates,name,'.$directorate->id],
            'code' => ['required', 'string', 'max:20', 'unique:directorates,code,'.$directorate->id],
            'type' => ['required', 'string', 'in:Directorate,CG Secretary'],
        ]);

        $directorate->update($validated);

        return redirect()->route('directorates.index')->with('success', 'Directorate updated successfully.');
    }
}
