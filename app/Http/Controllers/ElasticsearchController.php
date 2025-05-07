<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ElasticsearchController extends Controller
{
    public function deleteIndexForm()
    {
        $index = 'googlesheet-api-index-' . now()->format('Y-m-d');
        return view('elasticsearch.delete-index', compact('index'));
    }

    public function deleteIndex(Request $request, $index)
    {
        try {
            $elkHost = env('ELK_HOST');
            $response = Http::delete("{$elkHost}/{$index}");

            // Check for a successful response
            if ($response->successful()) {
                $message = "Index '{$index}' has been successfully deleted.";
                $status = 'success';
            } else {
                $message = "Failed to delete index '{$index}'. Error: " . $response->body();
                $status = 'error';
            }
        } catch (\Exception $e) {
            // Handle connection issues or other exceptions
            $message = "Error: {$e->getMessage()}";
            $status = 'error';
        }

        return view('elasticsearch.delete-index', [
            'message' => $message,
            'status' => $status,
            'index' => $index,
        ]);
    }
}
