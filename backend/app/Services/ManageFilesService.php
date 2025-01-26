<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ManageFilesService
{
    public function uploadFile($request, $inputName, $category)
    {
        $filePath = null;
        if ($request->hasFile($inputName)) {
            $file = $request->file($inputName);
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $fileName = $originalName . '_' . date('YmdHis') . '.' . $file->extension();
            $filePath = $file->storeAs('public/uploads/' . $category, $fileName);
            return $filePath;
        }
        return $filePath;
    }

    public function uploadMultipleFile($request, $files, $category)
    {
        $results = [];
        if ($request->hasFile($files)) {
            foreach ($request->file($files) as $key => $file) {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $fileName = $originalName . '_' . date('YmdHis') . '_' . uniqid() . '.' . $file->extension();
                $filePath = $file->storeAs('public/uploads/' . $category, $fileName);
                $results[] = ['filePath' => $filePath];
            }
            return $results;
        }
        return $results;
    }

    public function deleteFile($filePath)
    {
        if (Storage::exists($filePath)) {
            Storage::delete($filePath);
            return true;
        }
        return false;
    }

    public function deleteMultipleFile($filePaths)
    {
        $results = [];
        foreach ($filePaths as $file_path) {
            if (Storage::exists($file_path)) {
                Storage::delete($file_path);
                array_push($results, 'Successfully: ' . $file_path);
            } else {
                array_push($results, 'Failed: ' . $file_path);
            }
        }
        return $results;
    }

    public function deleteFolder($folderPath)
    {
        if (Storage::exists($folderPath)) {
            Storage::deleteDirectory($folderPath);
            return true;
        }
        return false;
    }

    public function deleteMultipleFolder($folderPaths)
    {
        $results = [];
        foreach ($folderPaths as $folder_path) {
            if (Storage::exists($folder_path)) {
                Storage::deleteDirectory($folder_path);
                array_push($results, 'Successfully: ' . $folder_path);
            } else {
                array_push($results, 'Failed: ' . $folder_path);
            }
        }
        return $results;
    }
}
