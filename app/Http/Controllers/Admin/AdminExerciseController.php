<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\HepProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminExerciseController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Display a listing of exercises
     */
    public function index(Request $request)
    {
        $query = Exercise::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('instructions', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Difficulty filter
        if ($request->filled('difficulty')) {
            $query->where('difficulty_level', $request->difficulty);
        }

        // Equipment filter
        if ($request->filled('equipment')) {
            $query->whereJsonContains('equipment_required', $request->equipment);
        }

        $exercises = $query->orderBy('name')->paginate(20);

        // Get filter options
        $categories = Exercise::getCategories();
        $difficulties = Exercise::getDifficultyLevels();

        // Get equipment options from existing exercises
        $equipmentOptions = Exercise::whereNotNull('equipment_required')
            ->get()
            ->pluck('equipment_required')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return view('admin.exercises.index', compact(
            'exercises',
            'categories',
            'difficulties',
            'equipmentOptions'
        ));
    }

    /**
     * Show the form for creating a new exercise
     */
    public function create()
    {
        $categories = Exercise::getCategories();
        $difficulties = Exercise::getDifficultyLevels();

        return view('admin.exercises.create', compact('categories', 'difficulties'));
    }

    /**
     * Store a newly created exercise
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => ['required', Rule::in(Exercise::getCategories())],
            'difficulty_level' => ['required', Rule::in(Exercise::getDifficultyLevels())],
            'instructions' => 'required|string',
            'contraindications' => 'nullable|array',
            'contraindications.*' => 'string|max:255',
            'equipment_required' => 'nullable|array',
            'equipment_required.*' => 'string|max:255',
            'target_muscle_groups' => 'nullable|array',
            'target_muscle_groups.*' => 'string|max:255',
            'duration' => 'nullable|integer|min:1',
            'video_url' => 'nullable|url',
            'image_url' => 'nullable|url',
            'video_file' => 'nullable|file|mimes:mp4,mov,avi,webm|max:51200', // 50MB max
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        try {
            // Handle file uploads
            if ($request->hasFile('video_file')) {
                $videoPath = $request->file('video_file')->store('exercises/videos', 'public');
                $validated['video_url'] = Storage::url($videoPath);
            }

            if ($request->hasFile('image_file')) {
                $imagePath = $request->file('image_file')->store('exercises/images', 'public');
                $validated['image_url'] = Storage::url($imagePath);
            }

            // Convert arrays to JSON
            $validated['contraindications'] = $validated['contraindications'] ?? [];
            $validated['equipment_required'] = $validated['equipment_required'] ?? [];
            $validated['target_muscle_groups'] = $validated['target_muscle_groups'] ?? [];

            $exercise = Exercise::create($validated);

            return redirect()->route('admin.exercises.show', $exercise)
                ->with('success', 'Exercise created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create exercise: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified exercise
     */
    public function show(Exercise $exercise)
    {
        $exercise->load(['hepExercises.hepProgram']);

        // Get usage statistics
        $usageCount = $exercise->hepExercises()->count();
        $activePrograms = $exercise->hepExercises()
            ->whereHas('hepProgram', function ($query) {
                $query->where('status', 'active');
            })
            ->count();

        return view('admin.exercises.show', compact('exercise', 'usageCount', 'activePrograms'));
    }

    /**
     * Show the form for editing the specified exercise
     */
    public function edit(Exercise $exercise)
    {
        $categories = Exercise::getCategories();
        $difficulties = Exercise::getDifficultyLevels();

        return view('admin.exercises.edit', compact('exercise', 'categories', 'difficulties'));
    }

    /**
     * Update the specified exercise
     */
    public function update(Request $request, Exercise $exercise)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => ['required', Rule::in(Exercise::getCategories())],
            'difficulty_level' => ['required', Rule::in(Exercise::getDifficultyLevels())],
            'instructions' => 'required|string',
            'contraindications' => 'nullable|array',
            'contraindications.*' => 'string|max:255',
            'equipment_required' => 'nullable|array',
            'equipment_required.*' => 'string|max:255',
            'target_muscle_groups' => 'nullable|array',
            'target_muscle_groups.*' => 'string|max:255',
            'duration' => 'nullable|integer|min:1',
            'video_url' => 'nullable|url',
            'image_url' => 'nullable|url',
            'video_file' => 'nullable|file|mimes:mp4,mov,avi,webm|max:51200',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        try {
            // Handle file uploads
            if ($request->hasFile('video_file')) {
                // Delete old video if exists
                if ($exercise->video_url && str_contains($exercise->video_url, '/storage/')) {
                    $oldPath = str_replace('/storage/', '', $exercise->video_url);
                    Storage::disk('public')->delete($oldPath);
                }

                $videoPath = $request->file('video_file')->store('exercises/videos', 'public');
                $validated['video_url'] = Storage::url($videoPath);
            }

            if ($request->hasFile('image_file')) {
                // Delete old image if exists
                if ($exercise->image_url && str_contains($exercise->image_url, '/storage/')) {
                    $oldPath = str_replace('/storage/', '', $exercise->image_url);
                    Storage::disk('public')->delete($oldPath);
                }

                $imagePath = $request->file('image_file')->store('exercises/images', 'public');
                $validated['image_url'] = Storage::url($imagePath);
            }

            // Convert arrays to JSON
            $validated['contraindications'] = $validated['contraindications'] ?? [];
            $validated['equipment_required'] = $validated['equipment_required'] ?? [];
            $validated['target_muscle_groups'] = $validated['target_muscle_groups'] ?? [];

            $exercise->update($validated);

            return redirect()->route('admin.exercises.show', $exercise)
                ->with('success', 'Exercise updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update exercise: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified exercise
     */
    public function destroy(Exercise $exercise)
    {
        try {
            // Check if exercise is being used in any programs
            if ($exercise->hepExercises()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete exercise that is currently used in HEP programs.');
            }

            // Delete associated files
            if ($exercise->video_url && str_contains($exercise->video_url, '/storage/')) {
                $videoPath = str_replace('/storage/', '', $exercise->video_url);
                Storage::disk('public')->delete($videoPath);
            }

            if ($exercise->image_url && str_contains($exercise->image_url, '/storage/')) {
                $imagePath = str_replace('/storage/', '', $exercise->image_url);
                Storage::disk('public')->delete($imagePath);
            }

            $exercise->delete();

            return redirect()->route('admin.exercises.index')
                ->with('success', 'Exercise deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->route('admin.exercises.index')
                ->with('error', 'Failed to delete exercise: ' . $e->getMessage());
        }
    }

    /**
     * Export exercises to CSV
     */
    public function export(Request $request)
    {
        $query = Exercise::query();

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty_level', $request->difficulty);
        }

        $exercises = $query->orderBy('name')->get();

        $filename = 'exercises-export-' . now()->format('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($exercises) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'ID',
                'Name',
                'Category',
                'Difficulty Level',
                'Description',
                'Instructions',
                'Equipment Required',
                'Target Muscle Groups',
                'Contraindications',
                'Duration (seconds)',
                'Video URL',
                'Image URL',
                'Created At',
                'Updated At'
            ]);

            // CSV data
            foreach ($exercises as $exercise) {
                fputcsv($file, [
                    $exercise->id,
                    $exercise->name,
                    $exercise->category,
                    $exercise->difficulty_level,
                    $exercise->description,
                    $exercise->instructions,
                    implode('; ', $exercise->equipment_required ?? []),
                    implode('; ', $exercise->target_muscle_groups ?? []),
                    implode('; ', $exercise->contraindications ?? []),
                    $exercise->duration,
                    $exercise->video_url,
                    $exercise->image_url,
                    $exercise->created_at->format('Y-m-d H:i:s'),
                    $exercise->updated_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show import form
     */
    public function importForm()
    {
        return view('admin.exercises.import');
    }

    /**
     * Import exercises from CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120', // 5MB max
        ]);

        try {
            $file = $request->file('csv_file');
            $path = $file->getRealPath();

            $csvData = array_map('str_getcsv', file($path));
            $header = array_shift($csvData);

            // Expected headers
            $expectedHeaders = [
                'Name',
                'Category',
                'Difficulty Level',
                'Description',
                'Instructions',
                'Equipment Required',
                'Target Muscle Groups',
                'Contraindications',
                'Duration (seconds)',
                'Video URL',
                'Image URL'
            ];

            // Validate headers
            if ($header !== $expectedHeaders) {
                return redirect()->back()
                    ->with('error', 'Invalid CSV format. Please use the correct template.');
            }

            $imported = 0;
            $errors = [];

            foreach ($csvData as $rowIndex => $row) {
                try {
                    $data = array_combine($header, $row);

                    // Validate required fields
                    if (empty(trim($data['Name'])) || empty(trim($data['Description']))) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Name and Description are required";
                        continue;
                    }

                    // Validate category
                    if (!in_array($data['Category'], Exercise::getCategories())) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Invalid category '{$data['Category']}'";
                        continue;
                    }

                    // Validate difficulty
                    if (!in_array($data['Difficulty Level'], Exercise::getDifficultyLevels())) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Invalid difficulty level '{$data['Difficulty Level']}'";
                        continue;
                    }

                    // Parse arrays
                    $equipment = !empty($data['Equipment Required']) ?
                        array_map('trim', explode(';', $data['Equipment Required'])) : [];

                    $muscleGroups = !empty($data['Target Muscle Groups']) ?
                        array_map('trim', explode(';', $data['Target Muscle Groups'])) : [];

                    $contraindications = !empty($data['Contraindications']) ?
                        array_map('trim', explode(';', $data['Contraindications'])) : [];

                    Exercise::create([
                        'name' => trim($data['Name']),
                        'category' => trim($data['Category']),
                        'difficulty_level' => trim($data['Difficulty Level']),
                        'description' => trim($data['Description']),
                        'instructions' => trim($data['Instructions']),
                        'equipment_required' => $equipment,
                        'target_muscle_groups' => $muscleGroups,
                        'contraindications' => $contraindications,
                        'duration' => !empty($data['Duration (seconds)']) ? (int)$data['Duration (seconds)'] : null,
                        'video_url' => !empty($data['Video URL']) ? trim($data['Video URL']) : null,
                        'image_url' => !empty($data['Image URL']) ? trim($data['Image URL']) : null,
                    ]);

                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            $message = "Import completed. {$imported} exercises imported successfully.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= " (and " . (count($errors) - 5) . " more errors)";
                }
            }

            return redirect()->route('admin.exercises.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download CSV template
     */
    public function downloadTemplate()
    {
        $filename = 'exercise-import-template.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Name',
                'Category',
                'Difficulty Level',
                'Description',
                'Instructions',
                'Equipment Required',
                'Target Muscle Groups',
                'Contraindications',
                'Duration (seconds)',
                'Video URL',
                'Image URL'
            ]);

            // Sample data
            fputcsv($file, [
                'Wall Push-ups',
                'strength',
                'beginner',
                'A beginner-friendly upper body exercise performed against a wall.',
                'Stand facing a wall with feet shoulder-width apart. Place hands on wall at shoulder height. Bend elbows to lower chest toward wall, then push back to start position.',
                'None',
                'Chest; Shoulders; Triceps',
                'Shoulder impingement; Recent upper body injury',
                '30',
                'https://example.com/video.mp4',
                'https://example.com/image.jpg'
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
