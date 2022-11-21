<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function download(Request $request)
    {
        $this->validate($request, [
            'path' => 'required|string',
            'name' => 'required|string'
        ]);

        $file = config('upload.storage') . $request->get('path');
        if(!file_exists($file)) abort(404);
        return response()->download($file, $request->get('name'));
    }
}
