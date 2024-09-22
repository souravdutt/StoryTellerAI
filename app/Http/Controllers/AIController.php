<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WorkersAIService;
use App\Services\HuggingFaceService;
use Illuminate\Support\Facades\Validator;

class AIController extends Controller
{
    protected $workersAI;

    public function __construct(WorkersAIService $workersAI)
    {
        $this->workersAI = $workersAI;
    }

    public function index()
    {
        return view('welcome');
    }

    public function generateText(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'generateText' => 'required|string|min:3',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $this->workersAI->generateText($request->generateText);
    }

    public function summarizeText(Request $request)
    {
        $text = $request->input('text');
        $result = $this->workersAI->summarizeText($text);

        return response()->json($result);
    }

}
