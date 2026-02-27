<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;

class AttachmentController extends Controller
{
    public function index()
    {
        return Attachment::all();
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'file_name' => 'required|string',
            'file_path' => 'required|string',
        ]);

        $attachment = Attachment::create($validated);
        return response()->json($attachment, 201);
    }

    public function show(Attachment $attachment)
    {
        return $attachment;
    }

    public function update(Request $request, Attachment $attachment)
    {
        $attachment->update($request->all());
        return response()->json($attachment);
    }

    public function destroy(Attachment $attachment)
    {
        $attachment->delete();
        return response()->json(['message'=>'Attachment deleted']);
    }
}
