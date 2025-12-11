<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HepProgramTemplate;
use App\Models\HepTemplateExercise;
use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminHepTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Display a listing of templates
     */
    public function index(Request $request)
    {
        $query = HepProgramTemplate::with('creator');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Diagnosis type filter
        if ($request->filled('diagnosis_type')) {
            $query->where('diagnosis_type', $request->diagnosis_type);
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $templates = $query->orderBy('name')->paginate(20);

        // Get filter options
        $categories = HepProgramTemplate::getCategories();
        $diagnosisTypes = HepProgramTemplate::getDiagnosisTypes();

        return view('admin.hep-templates.index', compact(
            'templates',
            'categories',
            'diagnosisTypes'
        ));
    }

    /**
     * Show the form for creating a new template
     */
    public function create()
    {
        $categories = HepProgramTemplate::getCategories();
        $diagnosisTypes = HepProgramTemplate::getDiagnosisTypes();
        $exercises = Exercise::orderBy('name')->get();

        return view('admin.hep-templates.create', compact('categories', 'diagnosisTypes', 'exercises'));
    }

    /**
     * Store a newly created template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => ['required', Rule::in(HepProgramTemplate::getCategories())],
            'diagnosis_type' => ['nullable', Rule::in(HepProgramTemplate::getDiagnosisTypes())],
            'duration_weeks' => 'required|integer|min:1|max:52',
            'frequency_per_week' => 'required|integer|min:1|max:7',
            'goals' => 'nullable|array',
            'goals.*' => 'string|max:255',
            'precautions' => 'nullable|array',
            'precautions.*' => 'string|max:255',
            'is_active' => 'boolean',
            'exercises' => 'required|array|min:1',
            'exercises.*.exercise_id' => 'required|exists:exercises,id',
            'exercises.*.week_number' => 'required|integer|min:1',
            'exercises.*.sets' => 'nullable|integer|min:1',
            'exercises.*.reps' => 'nullable|integer|min:1',
            'exercises.*.duration_seconds' => 'nullable|integer|min:1',
            'exercises.*.rest_seconds' => 'nullable|integer|min:0',
            'exercises.*.frequency' => 'nullable|string|max:255',
            'exercises.*.progression_notes' => 'nullable|string',
            'exercises.*.order' => 'integer|min:0',
        ]);

        try {
            $validated['created_by'] = auth('admin')->id();
            $validated['goals'] = $validated['goals'] ?? [];
            $validated['precautions'] = $validated['precautions'] ?? [];
            $validated['is_active'] = $request->has('is_active');

            $template = HepProgramTemplate::create($validated);

            // Create template exercises
            if (isset($validated['exercises'])) {
                foreach ($validated['exercises'] as $exerciseData) {
                    HepTemplateExercise::create([
                        'hep_program_template_id' => $template->id,
                        'exercise_id' => $exerciseData['exercise_id'],
                        'sets' => $exerciseData['sets'] ?? null,
                        'reps' => $exerciseData['reps'] ?? null,
                        'duration_seconds' => $exerciseData['duration_seconds'] ?? null,
                        'rest_seconds' => $exerciseData['rest_seconds'] ?? null,
                        'frequency' => $exerciseData['frequency'] ?? null,
                        'progression_notes' => $exerciseData['progression_notes'] ?? null,
                        'week_number' => $exerciseData['week_number'],
                        'order' => $exerciseData['order'] ?? 0,
                    ]);
                }
            }

            return redirect()->route('admin.hep-templates.show', $template)
                ->with('success', 'HEP template created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create template: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified template
     */
    public function show(HepProgramTemplate $template)
    {
        $template->load(['creator', 'templateExercises.exercise', 'programs.patient']);

        // Get usage statistics
        $usageCount = $template->getUsageCount();
        $activePrograms = $template->programs()->where('status', 'active')->count();

        // Group exercises by week
        $exercisesByWeek = $template->templateExercises()
            ->with('exercise')
            ->orderBy('week_number')
            ->orderBy('order')
            ->get()
            ->groupBy('week_number');

        return view('admin.hep-templates.show', compact(
            'template',
            'usageCount',
            'activePrograms',
            'exercisesByWeek'
        ));
    }

    /**
     * Show the form for editing the specified template
     */
    public function edit(HepProgramTemplate $template)
    {
        $categories = HepProgramTemplate::getCategories();
        $diagnosisTypes = HepProgramTemplate::getDiagnosisTypes();
        $exercises = Exercise::orderBy('name')->get();

        // Group template exercises by week for easier editing
        $templateExercises = $template->templateExercises()
            ->with('exercise')
            ->orderBy('week_number')
            ->orderBy('order')
            ->get()
            ->groupBy('week_number');

        return view('admin.hep-templates.edit', compact(
            'template',
            'categories',
            'diagnosisTypes',
            'exercises',
            'templateExercises'
        ));
    }

    /**
     * Update the specified template
     */
    public function update(Request $request, HepProgramTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => ['required', Rule::in(HepProgramTemplate::getCategories())],
            'diagnosis_type' => ['nullable', Rule::in(HepProgramTemplate::getDiagnosisTypes())],
            'duration_weeks' => 'required|integer|min:1|max:52',
            'frequency_per_week' => 'required|integer|min:1|max:7',
            'goals' => 'nullable|array',
            'goals.*' => 'string|max:255',
            'precautions' => 'nullable|array',
            'precautions.*' => 'string|max:255',
            'is_active' => 'boolean',
            'exercises' => 'required|array|min:1',
            'exercises.*.exercise_id' => 'required|exists:exercises,id',
            'exercises.*.week_number' => 'required|integer|min:1',
            'exercises.*.sets' => 'nullable|integer|min:1',
            'exercises.*.reps' => 'nullable|integer|min:1',
            'exercises.*.duration_seconds' => 'nullable|integer|min:1',
            'exercises.*.rest_seconds' => 'nullable|integer|min:0',
            'exercises.*.frequency' => 'nullable|string|max:255',
            'exercises.*.progression_notes' => 'nullable|string',
            'exercises.*.order' => 'integer|min:0',
        ]);

        try {
            $validated['goals'] = $validated['goals'] ?? [];
            $validated['precautions'] = $validated['precautions'] ?? [];
            $validated['is_active'] = $request->has('is_active');

            $template->update($validated);

            // Delete existing template exercises
            $template->templateExercises()->delete();

            // Create new template exercises
            if (isset($validated['exercises'])) {
                foreach ($validated['exercises'] as $exerciseData) {
                    HepTemplateExercise::create([
                        'hep_program_template_id' => $template->id,
                        'exercise_id' => $exerciseData['exercise_id'],
                        'sets' => $exerciseData['sets'] ?? null,
                        'reps' => $exerciseData['reps'] ?? null,
                        'duration_seconds' => $exerciseData['duration_seconds'] ?? null,
                        'rest_seconds' => $exerciseData['rest_seconds'] ?? null,
                        'frequency' => $exerciseData['frequency'] ?? null,
                        'progression_notes' => $exerciseData['progression_notes'] ?? null,
                        'week_number' => $exerciseData['week_number'],
                        'order' => $exerciseData['order'] ?? 0,
                    ]);
                }
            }

            return redirect()->route('admin.hep-templates.show', $template)
                ->with('success', 'HEP template updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update template: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified template
     */
    public function destroy(HepProgramTemplate $template)
    {
        try {
            // Check if template is being used in any programs
            if ($template->programs()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete template that is currently used in HEP programs.');
            }

            $template->delete();

            return redirect()->route('admin.hep-templates.index')
                ->with('success', 'HEP template deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->route('admin.hep-templates.index')
                ->with('error', 'Failed to delete template: ' . $e->getMessage());
        }
    }

    /**
     * Toggle template active status
     */
    public function toggleActive(HepProgramTemplate $template)
    {
        try {
            $template->update(['is_active' => !$template->is_active]);

            $status = $template->is_active ? 'activated' : 'deactivated';

            return redirect()->back()
                ->with('success', "Template {$status} successfully.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update template status: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate a template
     */
    public function duplicate(HepProgramTemplate $template)
    {
        try {
            $newTemplate = HepProgramTemplate::create([
                'name' => $template->name . ' (Copy)',
                'description' => $template->description,
                'category' => $template->category,
                'diagnosis_type' => $template->diagnosis_type,
                'duration_weeks' => $template->duration_weeks,
                'frequency_per_week' => $template->frequency_per_week,
                'goals' => $template->goals,
                'precautions' => $template->precautions,
                'is_active' => false, // Start as inactive
                'created_by' => auth('admin')->id(),
            ]);

            // Copy template exercises
            foreach ($template->templateExercises as $exercise) {
                HepTemplateExercise::create([
                    'hep_program_template_id' => $newTemplate->id,
                    'exercise_id' => $exercise->exercise_id,
                    'sets' => $exercise->sets,
                    'reps' => $exercise->reps,
                    'duration_seconds' => $exercise->duration_seconds,
                    'rest_seconds' => $exercise->rest_seconds,
                    'frequency' => $exercise->frequency,
                    'progression_notes' => $exercise->progression_notes,
                    'week_number' => $exercise->week_number,
                    'order' => $exercise->order,
                ]);
            }

            return redirect()->route('admin.hep-templates.edit', $newTemplate)
                ->with('success', 'Template duplicated successfully. You can now edit the copy.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to duplicate template: ' . $e->getMessage());
        }
    }
}
