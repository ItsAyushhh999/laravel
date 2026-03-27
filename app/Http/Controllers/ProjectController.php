<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    // ===================
    // Show all projects
    // ===================

    public function index()
    {
        return Project::all();
    }

    // ==========================
    // Create a project
    // ==========================

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $project = Project::create($validated);

        return response()->json($project, 201);
    }

    // =================================
    // Show one project
    // =================================

    public function show(Project $project)
    {
        return $project;
    }

    // ==================================
    // Update project details
    // ==================================
    public function update(Request $request, Project $project)
    {
        $project->update($request->all());

        return response()->json($project);
    }

    // ===================================
    // Delete a project
    // ===================================

    public function destroy(Project $project)
    {
        $project->delete();

        return response()->json(['message' => 'Project deleted']);
    }
}
