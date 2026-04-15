<?php

namespace App\Http\Controllers;

use App\Models\Formation;
use Illuminate\Http\Request;

/**
 * FormationController manages the creation of organizational units (Formations)
 * and their initial administrative personnel.
 */
class FormationController extends Controller
{
    private const TYPES = [
        'Zonal Command',
        'State Command',
        'Border Command',
        'Airport',
        'Marine',
        'Special Command',
    ];

    public function index()
    {
        $formations = Formation::query()
            ->leftJoin('formations as parents', 'formations.parent_id', '=', 'parents.id')
            ->select('formations.*')
            ->with('parent')
            ->whereNotNull('formations.code')
            ->where('formations.code', '<>', '')
            ->orderByRaw("coalesce(case when formations.type = 'Zonal Command' then formations.name else parents.name end, formations.name)")
            ->orderByRaw("case when formations.type = 'Zonal Command' then 0 else 1 end")
            ->orderBy('formations.name')
            ->paginate(20);

        return view('formations.index', compact('formations'));
    }

    /**
     * Show the form to create a new Formation.
     */
    public function create()
    {
        $types = self::TYPES;
        $zonalCommands = Formation::where('type', 'Zonal Command')->orderBy('name')->get();

        return view('formations.create', compact('types', 'zonalCommands'));
    }

    /**
     * Store the new Formation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'formation_name' => ['required', 'string', 'max:255', 'unique:formations,name'],
            'formation_code' => ['required', 'string', 'max:50', 'unique:formations,code'],
            'formation_type' => ['required', 'string', 'in:'.implode(',', self::TYPES)],
            'parent_id' => ['nullable', 'integer', 'exists:formations,id'],
        ]);

        $type = (string) $validated['formation_type'];
        $parentId = $request->filled('parent_id') ? (int) $request->parent_id : null;
        if ($type === 'Zonal Command') {
            $parentId = null;
        } else {
            if ($parentId === null) {
                return back()->withErrors(['parent_id' => 'Please select a Zonal Command parent.'])->withInput();
            }

            $ok = Formation::query()
                ->whereKey($parentId)
                ->where('type', 'Zonal Command')
                ->exists();

            if (! $ok) {
                return back()->withErrors(['parent_id' => 'Invalid parent selection.'])->withInput();
            }
        }

        Formation::create([
            'name' => $validated['formation_name'],
            'code' => $validated['formation_code'],
            'type' => $type,
            'parent_id' => $parentId,
        ]);

        return redirect()->route('formations.index')->with('success', 'Formation created successfully.');
    }

    public function edit(Formation $formation)
    {
        $types = self::TYPES;
        $zonalCommands = Formation::where('type', 'Zonal Command')->orderBy('name')->get();

        return view('formations.edit', compact('formation', 'types', 'zonalCommands'));
    }

    public function update(Request $request, Formation $formation)
    {
        $validated = $request->validate([
            'formation_name' => ['required', 'string', 'max:255', 'unique:formations,name,'.$formation->id],
            'formation_code' => ['required', 'string', 'max:50', 'unique:formations,code,'.$formation->id],
            'formation_type' => ['required', 'string', 'in:'.implode(',', self::TYPES)],
            'parent_id' => ['nullable', 'integer', 'exists:formations,id'],
        ]);

        $type = (string) $validated['formation_type'];
        $parentId = $request->filled('parent_id') ? (int) $request->parent_id : null;
        if ($type === 'Zonal Command') {
            $parentId = null;
        } else {
            if ($parentId === null) {
                return back()->withErrors(['parent_id' => 'Please select a Zonal Command parent.'])->withInput();
            }

            $ok = Formation::query()
                ->whereKey($parentId)
                ->where('type', 'Zonal Command')
                ->exists();

            if (! $ok) {
                return back()->withErrors(['parent_id' => 'Invalid parent selection.'])->withInput();
            }
        }

        $formation->update([
            'name' => $validated['formation_name'],
            'code' => $validated['formation_code'],
            'type' => $type,
            'parent_id' => $parentId,
        ]);

        return redirect()->route('formations.index')->with('success', 'Formation updated successfully.');
    }

    public function destroy(Formation $formation)
    {
        $hasChildren = Formation::query()->where('parent_id', $formation->id)->exists();
        if ($hasChildren) {
            return redirect()->route('formations.index')->with('error', 'Cannot delete a formation that has child formations.');
        }

        $formation->delete();

        return redirect()->route('formations.index')->with('success', 'Formation deleted successfully.');
    }
}
