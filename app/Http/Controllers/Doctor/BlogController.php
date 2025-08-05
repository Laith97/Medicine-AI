<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorBlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'doctor']);
    }

    public function index()
    {
        $doctor = $this->getEffectiveDoctor();
        
        $posts = $doctor->blogPosts()
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

        return view('doctor.blog.index', compact('posts'));
    }

    public function create()
    {
        return view('doctor.blog.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'short_description' => 'required|string|max:500',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_published' => 'boolean',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_keywords' => 'nullable|string|max:255',
        ]);

        $data = $request->only([
            'title', 'short_description', 'content', 'is_published'
        ]);

        $doctor = $this->getEffectiveDoctor();
        $data['doctor_id'] = $doctor->id;
        $data['is_published'] = $request->boolean('is_published');

        // Handle SEO meta data
        $seoMeta = [];
        if ($request->filled('seo_title')) {
            $seoMeta['title'] = $request->seo_title;
        }
        if ($request->filled('seo_description')) {
            $seoMeta['description'] = $request->seo_description;
        }
        if ($request->filled('seo_keywords')) {
            $seoMeta['keywords'] = $request->seo_keywords;
        }
        if (!empty($seoMeta)) {
            $data['seo_meta'] = $seoMeta;
        }

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')
                                               ->store('blog-images', 'public');
        }

        $post = DoctorBlogPost::create($data);

        return redirect()->route('doctor.blog.index')
                        ->with('success', 'Blog post created successfully!');
    }

    public function show(DoctorBlogPost $post)
    {
        $this->authorize('view', $post);

        return view('doctor.blog.show', compact('post'));
    }

    public function edit(DoctorBlogPost $post)
    {
        $this->authorize('update', $post);

        return view('doctor.blog.edit', compact('post'));
    }

    public function update(Request $request, DoctorBlogPost $post)
    {
        $this->authorize('update', $post);

        $request->validate([
            'title' => 'required|string|max:255',
            'short_description' => 'required|string|max:500',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_published' => 'boolean',
            'remove_image' => 'boolean',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_keywords' => 'nullable|string|max:255',
        ]);

        $data = $request->only([
            'title', 'short_description', 'content', 'is_published'
        ]);

        $data['is_published'] = $request->boolean('is_published');

        // Handle SEO meta data
        $seoMeta = $post->seo_meta ?? [];
        if ($request->filled('seo_title')) {
            $seoMeta['title'] = $request->seo_title;
        }
        if ($request->filled('seo_description')) {
            $seoMeta['description'] = $request->seo_description;
        }
        if ($request->filled('seo_keywords')) {
            $seoMeta['keywords'] = $request->seo_keywords;
        }
        $data['seo_meta'] = $seoMeta;

        // Handle image removal
        if ($request->boolean('remove_image') && $post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
            $data['featured_image'] = null;
        }

        // Handle new featured image upload
        if ($request->hasFile('featured_image')) {
            // Delete old image if exists
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }

            $data['featured_image'] = $request->file('featured_image')
                                               ->store('blog-images', 'public');
        }

        $post->update($data);

        return redirect()->route('doctor.blog.index')
                        ->with('success', 'Blog post updated successfully!');
    }

    public function destroy(DoctorBlogPost $post)
    {
        $this->authorize('delete', $post);

        // Delete featured image if exists
        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
        }

        $post->delete();

        return redirect()->route('doctor.blog.index')
                        ->with('success', 'Blog post deleted successfully!');
    }

    public function togglePublish(Request $request, DoctorBlogPost $post)
    {
        $this->authorize('update', $post);

        $post->update([
            'is_published' => !$post->is_published,
            'published_at' => !$post->is_published ? now() : null
        ]);

        $status = $post->is_published ? 'published' : 'unpublished';

        return response()->json([
            'success' => true,
            'is_published' => $post->is_published,
            'message' => "Blog post {$status} successfully!"
        ]);
    }
}
