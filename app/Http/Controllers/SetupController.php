<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SetupController extends Controller
{
    public function show()
    {
        return view('setup');
    }

    public function store(Request $request)
    {
        $request->validate([
            'credentials_file' => 'required|file|mimes:json',
        ]);

        $uploadedFile = $request->file('credentials_file');
        $destination = storage_path('app/google_credentials.json');

        // Move file to storage
        $uploadedFile->move(storage_path('app'), 'google_credentials.json');

        // Update .env
        $envPath = base_path('.env');
        $newValue = 'CREDENTIALS_FILE=' . $destination;

        if (File::exists($envPath)) {
            $envContent = File::get($envPath);

            if (str_contains($envContent, 'CREDENTIALS_FILE=')) {
                $envContent = preg_replace('/^CREDENTIALS_FILE=.*$/m', $newValue, $envContent);
            } else {
                $envContent .= "\n" . $newValue;
            }

            File::put($envPath, $envContent);
        }

        return redirect()->route('setup.show')->with('success', 'Credentials saved successfully!');
    }
}
