<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $files = Storage::disk('public')->files('uploads');
        $fileList = [];

        foreach ($files as $file) {
            $fileList[] = [
                'name' => basename($file),
                'url' => url(Storage::url($file)),
                'size' => Storage::disk('public')->size($file),
                'last_modified' => Storage::disk('public')->lastModified($file),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $fileList
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file', // No size limit, accepts any type
        ]);

        $uploadedData = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                
                $path = $file->storeAs('uploads', $fileName, 'public');

                $uploadedData[] = [
                    'name' => $fileName,
                    'url' => url(Storage::url($path)),
                    'size' => $file->getSize(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully',
                'data' => $uploadedData
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'No files uploaded',
        ], 400);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($filename)
    {
        $path = 'uploads/' . $filename;
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'File not found',
        ], 404);
    }
}
