<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomFieldController extends Controller
{
    public function edit(Request $request, CustomField $customField)
    {
        $currentUser = $request->user();
        if (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())) {
            abort(403);
        }

        return view('custom-fields.edit', compact('customField'));
    }

    public function update(Request $request, CustomField $customField)
    {
        $currentUser = $request->user();
        if (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())) {
            abort(403);
        }

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'key' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('custom_fields', 'key')
                    ->ignore($customField->id)
                    ->whereNull('deleted_at'),
            ],
            'type' => ['required', 'string', Rule::in(['text', 'textarea', 'number', 'date', 'select'])],
            'required' => ['nullable', 'boolean'],
            'options' => ['nullable', 'string'],
        ]);

        $key = $validated['key'] ?? '';
        if ($key === '') {
            $key = Str::of($validated['label'])->lower()->trim()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
        }

        if ($key === '' || ! preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
            return back()->withErrors(['key' => 'Invalid key format. Use lowercase letters, numbers and underscore only.'])->withInput();
        }

        $options = null;
        if ($validated['type'] === 'select') {
            $raw = (string) ($validated['options'] ?? '');
            $parts = collect(explode(',', $raw))
                ->map(fn ($p) => trim($p))
                ->filter()
                ->values()
                ->all();

            if ($parts === []) {
                return back()->withErrors(['options' => 'Please provide comma-separated options for Select type.'])->withInput();
            }

            $options = $parts;
        }

        $customField->update([
            'key' => $key,
            'label' => $validated['label'],
            'type' => $validated['type'],
            'required' => (bool) ($validated['required'] ?? false),
            'options' => $options,
        ]);

        return redirect()->route('personnel.index', ['tab' => 'custom'])->with('success', 'Custom field updated successfully.');
    }

    public function store(Request $request)
    {
        $currentUser = $request->user();
        if (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())) {
            abort(403);
        }

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'key' => ['nullable', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:custom_fields,key'],
            'type' => ['required', 'string', Rule::in(['text', 'textarea', 'number', 'date', 'select'])],
            'required' => ['nullable', 'boolean'],
            'options' => ['nullable', 'string'],
        ]);

        $key = $validated['key'] ?? '';
        if ($key === '') {
            $key = Str::of($validated['label'])->lower()->trim()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
        }

        if ($key === '' || ! preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
            return back()->withErrors(['key' => 'Invalid key format. Use lowercase letters, numbers and underscore only.'])->withInput();
        }

        if (CustomField::where('key', $key)->exists()) {
            return back()->withErrors(['key' => 'Key already exists.'])->withInput();
        }

        $options = null;
        if ($validated['type'] === 'select') {
            $raw = (string) ($validated['options'] ?? '');
            $parts = collect(explode(',', $raw))
                ->map(fn ($p) => trim($p))
                ->filter()
                ->values()
                ->all();

            if ($parts === []) {
                return back()->withErrors(['options' => 'Please provide comma-separated options for Select type.'])->withInput();
            }

            $options = $parts;
        }

        CustomField::create([
            'key' => $key,
            'label' => $validated['label'],
            'type' => $validated['type'],
            'required' => (bool) ($validated['required'] ?? false),
            'options' => $options,
            'created_by' => $currentUser->id,
        ]);

        return redirect()->route('personnel.index', ['tab' => 'custom'])->with('success', 'Custom field created successfully.');
    }

    public function destroy(Request $request, CustomField $customField)
    {
        $currentUser = $request->user();
        if (! ($currentUser->isMainAdmin() || $currentUser->isSuperAdmin())) {
            abort(403);
        }

        $customField->delete();

        return redirect()->route('personnel.index', ['tab' => 'custom'])->with('success', 'Custom field deleted successfully.');
    }
}
