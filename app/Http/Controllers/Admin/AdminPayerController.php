<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payer;
use Illuminate\Http\Request;

class AdminPayerController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Display a listing of payers
     */
    public function index(Request $request)
    {
        $query = Payer::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('payer_id', 'like', "%{$search}%");
            });
        }

        $payers = $query->orderBy('name')->paginate(20);

        return view('admin.payers.index', compact('payers'));
    }

    /**
     * Show the form for creating a new payer
     */
    public function create()
    {
        return view('admin.payers.create');
    }

    /**
     * Store a newly created payer
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'payer_id' => 'required|string|max:100|unique:payers,payer_id',
            'contact_info' => 'nullable|array',
            'contact_info.email' => 'nullable|email',
            'contact_info.phone' => 'nullable|string|max:20',
            'contact_info.address' => 'nullable|string',
            'settings' => 'nullable|array',
            'settings.processing_time_days' => 'nullable|integer|min:1|max:365',
            'settings.requires_pre_auth' => 'nullable|boolean',
            'settings.auto_approve_under' => 'nullable|numeric|min:0',
        ]);

        try {
            $payer = Payer::create($validated);

            return redirect()->route('admin.payers.show', $payer)
                ->with('success', 'Payer created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create payer: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified payer
     */
    public function show(Payer $payer)
    {
        $payer->load(['rules.ruleType']);

        // Get statistics
        $totalRules = $payer->rules()->count();
        $activeRules = $payer->rules()->where('is_active', true)->count();
        $recentApplications = $payer->rules()
            ->with(['applications' => function($query) {
                $query->latest()->limit(10);
            }])
            ->get()
            ->pluck('applications')
            ->flatten()
            ->sortByDesc('created_at')
            ->take(10);

        return view('admin.payers.show', compact('payer', 'totalRules', 'activeRules', 'recentApplications'));
    }

    /**
     * Show the form for editing the specified payer
     */
    public function edit(Payer $payer)
    {
        return view('admin.payers.edit', compact('payer'));
    }

    /**
     * Update the specified payer
     */
    public function update(Request $request, Payer $payer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'payer_id' => 'required|string|max:100|unique:payers,payer_id,' . $payer->id,
            'contact_info' => 'nullable|array',
            'contact_info.email' => 'nullable|email',
            'contact_info.phone' => 'nullable|string|max:20',
            'contact_info.address' => 'nullable|string',
            'settings' => 'nullable|array',
            'settings.processing_time_days' => 'nullable|integer|min:1|max:365',
            'settings.requires_pre_auth' => 'nullable|boolean',
            'settings.auto_approve_under' => 'nullable|numeric|min:0',
        ]);

        try {
            $payer->update($validated);

            return redirect()->route('admin.payers.show', $payer)
                ->with('success', 'Payer updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update payer: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified payer
     */
    public function destroy(Payer $payer)
    {
        try {
            // Check if payer has associated rules
            if ($payer->rules()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete payer that has associated rules.');
            }

            $payer->delete();

            return redirect()->route('admin.payers.index')
                ->with('success', 'Payer deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->route('admin.payers.index')
                ->with('error', 'Failed to delete payer: ' . $e->getMessage());
        }
    }
}
