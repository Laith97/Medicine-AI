<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorLandingPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LandingPageController extends Controller
{
    /**
     * Display the landing page management interface
     */
    public function index()
    {
        $doctor = $this->getEffectiveDoctor();

        if (!$doctor) {
            return redirect()->route('dashboard')->with('error', 'Doctor profile not found.');
        }

        $landingPage = $doctor->landingPage;

        // Create default landing page if it doesn't exist
        if (!$landingPage) {
            $landingPage = $this->createDefaultLandingPage($doctor);
        }

        return view('doctor.landing-page.index', compact('landingPage'));
    }

    /**
     * Update the landing page settings
     */
    public function update(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();

        if (!$doctor) {
            return response()->json(['error' => 'Doctor profile not found.'], 404);
        }

        $request->validate([
            'username' => 'required|string|max:50|regex:/^[a-zA-Z0-9_-]+$/|unique:doctor_landing_pages,username,' . ($doctor->landingPage->id ?? 'NULL'),
            'template' => 'required|in:template1,template2,template3,template4',
            'page_title' => 'nullable|string|max:255',
            'page_description' => 'nullable|string|max:500',
            'tagline' => 'nullable|string|max:255',
            'about_text' => 'nullable|string|max:2000',
            'colors' => 'nullable|array',
            'section_visibility' => 'nullable|array',
            'custom_domain' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            'subdomain_enabled' => 'boolean',
            'default_language' => 'nullable|string|in:en,ar',
            'translations' => 'nullable|array',
            // Page builder fields
            'page_sections' => 'nullable|array',
            'navbar_config' => 'nullable|array',
            'animations_config' => 'nullable|array',
            'custom_css' => 'nullable|array',
            'fonts_config' => 'nullable|array',
            'background_config' => 'nullable|array',
            'button_styles' => 'nullable|array',
            'spacing_config' => 'nullable|array',
            'enable_animations' => 'boolean',
            'page_layout' => 'nullable|string|in:default,fullwidth,boxed,sidebar',
        ]);

        $landingPage = $doctor->landingPage ?: new DoctorLandingPage(['doctor_id' => $doctor->id]);

        $landingPage->fill($request->only([
            'username', 'template', 'page_title', 'page_description',
            'tagline', 'about_text', 'custom_domain', 'subdomain_enabled',
            'default_language', 'enable_animations', 'page_layout'
        ]));

        // Handle colors
        if ($request->has('colors')) {
            $landingPage->colors = $request->colors;
        }

        // Handle section visibility
        if ($request->has('section_visibility')) {
            $landingPage->section_visibility = $request->section_visibility;
        }

        // Handle translations
        if ($request->has('translations')) {
            $landingPage->translations = $request->translations;
        }

        // Handle page builder fields
        if ($request->has('page_sections')) {
            $landingPage->page_sections = $request->page_sections;
        }

        if ($request->has('navbar_config')) {
            $landingPage->navbar_config = $request->navbar_config;
        }

        if ($request->has('animations_config')) {
            $landingPage->animations_config = $request->animations_config;
        }

        if ($request->has('custom_css')) {
            $landingPage->custom_css = $request->custom_css;
        }

        if ($request->has('fonts_config')) {
            $landingPage->fonts_config = $request->fonts_config;
        }

        if ($request->has('background_config')) {
            $landingPage->background_config = $request->background_config;
        }

        if ($request->has('button_styles')) {
            $landingPage->button_styles = $request->button_styles;
        }

        if ($request->has('spacing_config')) {
            $landingPage->spacing_config = $request->spacing_config;
        }

        $landingPage->save();

        return response()->json([
            'success' => true,
            'message' => 'Landing page updated successfully!',
            'preview_url' => route('doctor.landing.preview', $landingPage->username)
        ]);
    }

    /**
     * Upload hero image
     */
    public function uploadHeroImage(Request $request)
    {
        $request->validate([
            'hero_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $doctor = $this->getEffectiveDoctor();

        if (!$doctor || !$doctor->landingPage) {
            return response()->json(['error' => 'Landing page not found.'], 404);
        }

        $landingPage = $doctor->landingPage;

        // Delete old image if exists
        if ($landingPage->hero_image) {
            Storage::disk('public')->delete($landingPage->hero_image);
        }

        // Store new image
        $path = $request->file('hero_image')->store('landing-pages/hero', 'public');
        $landingPage->hero_image = $path;
        $landingPage->save();

        return response()->json([
            'success' => true,
            'message' => 'Hero image uploaded successfully!',
            'image_url' => Storage::url($path)
        ]);
    }

    /**
     * Toggle landing page publication status
     */
    public function togglePublish(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();

        if (!$doctor || !$doctor->landingPage) {
            return response()->json(['error' => 'Landing page not found.'], 404);
        }

        $landingPage = $doctor->landingPage;
        $landingPage->is_published = !$landingPage->is_published;
        $landingPage->save();

        return response()->json([
            'success' => true,
            'is_published' => $landingPage->is_published,
            'message' => $landingPage->is_published ?
                'Landing page published successfully!' :
                'Landing page unpublished successfully!',
            'public_url' => $landingPage->is_published ? $landingPage->url : null
        ]);
    }

    /**
     * Preview the landing page
     */
    public function preview($username, Request $request)
    {
        $landingPage = DoctorLandingPage::with(['doctor.user', 'doctor.specialty'])
            ->byUsername($username)
            ->first();

        if (!$landingPage) {
            abort(404, 'Landing page not found.');
        }

        // Check if user owns this landing page or is admin
        if (auth()->check()) {
            $effectiveDoctor = auth()->user()->getEffectiveDoctor();
            if ($effectiveDoctor && $effectiveDoctor->id !== $landingPage->doctor_id && !auth()->user()->isAdmin()) {
                abort(403, 'Unauthorized access.');
            }
        }

        // Get language from request or use default
        $language = $request->get('lang', $landingPage->default_language ?: 'en');

        return $this->renderLandingPage($landingPage, true, $language);
    }

    /**
     * Create default landing page for doctor
     */
    private function createDefaultLandingPage($doctor)
    {
        $username = $this->generateUniqueUsername($doctor->user->name);

        return DoctorLandingPage::create([
            'doctor_id' => $doctor->id,
            'username' => $username,
            'template' => 'template1',
            'page_title' => $doctor->user->name . ' - ' . ($doctor->specialty->name ?? 'Medical Professional'),
            'page_description' => 'Book an appointment with ' . $doctor->user->name,
            'tagline' => 'Your Health, Our Priority',
            'about_text' => $doctor->bio ?? 'Experienced medical professional dedicated to providing quality healthcare.',
            'colors' => [],
            'section_visibility' => [],
            'is_published' => false,
        ]);
    }

    /**
     * Generate unique username from doctor name
     */
    private function generateUniqueUsername($name)
    {
        $baseUsername = Str::slug(strtolower($name), '');
        $username = $baseUsername;
        $counter = 1;

        while (DoctorLandingPage::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Render landing page with template
     */
    private function renderLandingPage($landingPage, $isPreview = false, $language = 'en')
    {
        $doctor = $landingPage->doctor;
        $reviews = $doctor->publicReviews()->with('patient')->latest()->limit(6)->get();

        // Get published blog posts if health tips section is enabled
        $blogPosts = collect();
        if (($landingPage->section_visibility['health_tips'] ?? true) && \Schema::hasTable('blog_posts')) {
            $blogPosts = $doctor->publishedBlogPosts()
                ->latest('published_at')
                ->limit(3)
                ->get();
        }

        // Get available slots for next 7 days
        $availableSlots = [];
        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i)->format('Y-m-d');
            $slots = $doctor->getAvailableSlots($date);
            if ($slots->isNotEmpty()) {
                $availableSlots[$date] = $slots->take(6); // Limit slots per day
            }
        }

        // Get translated content
        $translatedContent = $landingPage->getTranslatedContent($language);

        $templateView = 'doctor.landing-page.templates.' . $landingPage->template;

        return view($templateView, compact('landingPage', 'doctor', 'reviews', 'availableSlots', 'blogPosts', 'isPreview', 'language', 'translatedContent'));
    }

    /**
     * Show page builder interface
     */
    public function pageBuilder()
    {
        $doctor = $this->getEffectiveDoctor();

        if (!$doctor) {
            return redirect()->route('dashboard')->with('error', 'Doctor profile not found.');
        }

        $landingPage = $doctor->landingPage;

        // Create default landing page if it doesn't exist
        if (!$landingPage) {
            $landingPage = $this->createDefaultLandingPage($doctor);
        }

        // Get available section templates
        $sectionTemplates = $this->getSectionTemplates();

        return view('doctor.landing-page.page-builder', compact('landingPage', 'sectionTemplates'));
    }

    /**
     * Update page sections
     */
    public function updateSections(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();

        if (!$doctor || !$doctor->landingPage) {
            return response()->json(['error' => 'Landing page not found.'], 404);
        }

        $request->validate([
            'sections' => 'required|array',
            'sections.*.id' => 'required|string',
            'sections.*.type' => 'required|string',
            'sections.*.config' => 'required|array',
            'sections.*.order' => 'required|integer',
        ]);

        $landingPage = $doctor->landingPage;
        $landingPage->page_sections = $request->sections;
        $landingPage->save();

        return response()->json([
            'success' => true,
            'message' => 'Sections updated successfully!'
        ]);
    }

    /**
     * Get available section templates
     */
    public function getSectionTemplates()
    {
        return [
            'hero' => [
                'name' => 'Hero Section',
                'type' => 'hero',
                'category' => 'header',
                'description' => 'Main banner with title, subtitle, and call-to-action',
                'preview_image' => '/images/sections/hero-preview.jpg',
                'default_config' => [
                    'title' => 'Welcome to My Practice',
                    'subtitle' => 'Providing quality healthcare with compassion',
                    'background_type' => 'image',
                    'background_image' => '',
                    'background_color' => '#3b82f6',
                    'text_color' => '#ffffff',
                    'button_text' => 'Book Appointment',
                    'button_link' => '#appointments',
                    'animation' => 'fadeInUp',
                    'overlay_opacity' => 0.5,
                ]
            ],
            'about' => [
                'name' => 'About Section',
                'type' => 'about',
                'category' => 'content',
                'description' => 'Professional bio and credentials',
                'preview_image' => '/images/sections/about-preview.jpg',
                'default_config' => [
                    'title' => 'About Dr. [Name]',
                    'content' => 'Your professional bio goes here...',
                    'image' => '',
                    'layout' => 'image-left',
                    'background_color' => '#ffffff',
                    'text_color' => '#374151',
                    'animation' => 'fadeInLeft',
                ]
            ],
            'services' => [
                'name' => 'Services Section',
                'type' => 'services',
                'category' => 'content',
                'description' => 'List of medical services offered',
                'preview_image' => '/images/sections/services-preview.jpg',
                'default_config' => [
                    'title' => 'Our Services',
                    'subtitle' => 'Comprehensive healthcare solutions',
                    'services' => [
                        ['title' => 'General Consultation', 'description' => 'Comprehensive health checkups', 'icon' => 'fas fa-stethoscope'],
                        ['title' => 'Preventive Care', 'description' => 'Regular health screenings', 'icon' => 'fas fa-shield-alt'],
                        ['title' => 'Treatment Plans', 'description' => 'Personalized treatment approaches', 'icon' => 'fas fa-prescription-bottle-alt'],
                    ],
                    'layout' => 'grid-3',
                    'background_color' => '#f8fafc',
                    'animation' => 'fadeInUp',
                ]
            ],
            'testimonials' => [
                'name' => 'Testimonials Section',
                'type' => 'testimonials',
                'category' => 'social-proof',
                'description' => 'Patient reviews and testimonials',
                'preview_image' => '/images/sections/testimonials-preview.jpg',
                'default_config' => [
                    'title' => 'What Patients Say',
                    'subtitle' => 'Real experiences from our patients',
                    'layout' => 'carousel',
                    'show_ratings' => true,
                    'background_color' => '#ffffff',
                    'animation' => 'fadeIn',
                ]
            ],
            'contact' => [
                'name' => 'Contact Section',
                'type' => 'contact',
                'category' => 'footer',
                'description' => 'Contact information and form',
                'preview_image' => '/images/sections/contact-preview.jpg',
                'default_config' => [
                    'title' => 'Get In Touch',
                    'subtitle' => 'Contact us for appointments or inquiries',
                    'show_form' => true,
                    'show_map' => true,
                    'background_color' => '#f8fafc',
                    'animation' => 'fadeInUp',
                ]
            ],
            'cta' => [
                'name' => 'Call to Action',
                'type' => 'cta',
                'category' => 'conversion',
                'description' => 'Prominent call-to-action section',
                'preview_image' => '/images/sections/cta-preview.jpg',
                'default_config' => [
                    'title' => 'Ready to Get Started?',
                    'subtitle' => 'Book your appointment today',
                    'button_text' => 'Book Now',
                    'button_link' => '#appointments',
                    'background_type' => 'gradient',
                    'background_color' => '#3b82f6',
                    'text_color' => '#ffffff',
                    'animation' => 'pulse',
                ]
            ],
            'gallery' => [
                'name' => 'Image Gallery',
                'type' => 'gallery',
                'category' => 'media',
                'description' => 'Photo gallery of clinic/practice',
                'preview_image' => '/images/sections/gallery-preview.jpg',
                'default_config' => [
                    'title' => 'Our Facility',
                    'subtitle' => 'Take a look at our modern clinic',
                    'images' => [],
                    'layout' => 'masonry',
                    'columns' => 3,
                    'animation' => 'zoomIn',
                ]
            ],
            'faq' => [
                'name' => 'FAQ Section',
                'type' => 'faq',
                'category' => 'content',
                'description' => 'Frequently asked questions',
                'preview_image' => '/images/sections/faq-preview.jpg',
                'default_config' => [
                    'title' => 'Frequently Asked Questions',
                    'subtitle' => 'Common questions about our services',
                    'faqs' => [
                        ['question' => 'How do I book an appointment?', 'answer' => 'You can book online or call our office.'],
                        ['question' => 'What insurance do you accept?', 'answer' => 'We accept most major insurance plans.'],
                    ],
                    'layout' => 'accordion',
                    'animation' => 'fadeInUp',
                ]
            ]
        ];
    }

    /**
     * Upload section image
     */
    public function uploadSectionImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'section_id' => 'required|string',
        ]);

        $doctor = $this->getEffectiveDoctor();

        if (!$doctor || !$doctor->landingPage) {
            return response()->json(['error' => 'Landing page not found.'], 404);
        }

        // Store image
        $path = $request->file('image')->store('landing-pages/sections', 'public');

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully!',
            'image_url' => Storage::url($path),
            'path' => $path
        ]);
    }

    /**
     * Get animation presets
     */
    public function getAnimationPresets()
    {
        return [
            'entrance' => [
                'fadeIn' => 'Fade In',
                'fadeInUp' => 'Fade In Up',
                'fadeInDown' => 'Fade In Down',
                'fadeInLeft' => 'Fade In Left',
                'fadeInRight' => 'Fade In Right',
                'slideInUp' => 'Slide In Up',
                'slideInDown' => 'Slide In Down',
                'slideInLeft' => 'Slide In Left',
                'slideInRight' => 'Slide In Right',
                'zoomIn' => 'Zoom In',
                'zoomInUp' => 'Zoom In Up',
                'bounceIn' => 'Bounce In',
                'bounceInUp' => 'Bounce In Up',
                'rotateIn' => 'Rotate In',
                'flipInX' => 'Flip In X',
                'flipInY' => 'Flip In Y',
            ],
            'attention' => [
                'bounce' => 'Bounce',
                'flash' => 'Flash',
                'pulse' => 'Pulse',
                'rubberBand' => 'Rubber Band',
                'shake' => 'Shake',
                'swing' => 'Swing',
                'tada' => 'Tada',
                'wobble' => 'Wobble',
                'jello' => 'Jello',
                'heartBeat' => 'Heart Beat',
            ]
        ];
    }
}
