<?php

namespace App\Http\Controllers;

use App\Models\DoctorLandingPage;
use App\Models\LandingPageVisit;
use Illuminate\Http\Request;

class PublicLandingPageController extends Controller
{
    /**
     * Show public landing page by username
     */
    public function show($username, Request $request)
    {
        $landingPage = DoctorLandingPage::with(['doctor.user', 'doctor.specialty'])
            ->byUsername($username)
            ->published()
            ->first();

        if (!$landingPage) {
            abort(404, 'Doctor landing page not found.');
        }

        return $this->renderLandingPage($landingPage, $request);
    }

    /**
     * Show landing page by custom domain
     */
    public function showByDomain(Request $request)
    {
        $domain = $request->getHost();

        $landingPage = DoctorLandingPage::with(['doctor.user', 'doctor.specialty'])
            ->byCustomDomain($domain)
            ->published()
            ->first();

        if (!$landingPage) {
            abort(404, 'Doctor landing page not found.');
        }

        return $this->renderLandingPage($landingPage, $request);
    }

    /**
     * Show landing page by subdomain
     */
    public function showBySubdomain(Request $request)
    {
        $host = $request->getHost();
        $parts = explode('.', $host);

        if (count($parts) < 3) {
            abort(404, 'Invalid subdomain.');
        }

        $username = $parts[0];

        $landingPage = DoctorLandingPage::with(['doctor.user', 'doctor.specialty'])
            ->byUsername($username)
            ->where('subdomain_enabled', true)
            ->published()
            ->first();

        if (!$landingPage) {
            abort(404, 'Doctor landing page not found.');
        }

        return $this->renderLandingPage($landingPage, $request);
    }

    /**
     * Render landing page with template
     */
    private function renderLandingPage($landingPage, Request $request = null)
    {
        $doctor = $landingPage->doctor;

        // Record visit (if request is available)
        if ($request) {
            LandingPageVisit::recordVisit($doctor->id, $request);
        }

        // Get published blog posts if health tips section is enabled
        $blogPosts = collect();
        if (($landingPage->section_visibility['health_tips'] ?? true) && \Schema::hasTable('doctor_blog_posts')) {
            $blogPosts = $doctor->publishedBlogPosts()
                ->latest('published_at')
                ->limit(3)
                ->get();
        }

        // Only show public reviews if reviews section is enabled
        $reviews = collect();
        if ($landingPage->section_visibility['reviews'] ?? true) {
            $reviews = $doctor->publicReviews()
                ->latest()
                ->limit(6)
                ->get();
        }

        // Only get available slots if appointments section is enabled
        $availableSlots = [];
        if ($landingPage->section_visibility['appointments'] ?? true) {
            for ($i = 0; $i < 7; $i++) {
                $date = now()->addDays($i)->format('Y-m-d');
                $slots = $doctor->getAvailableSlots($date);
                if ($slots->isNotEmpty()) {
                    $availableSlots[$date] = $slots->take(6); // Limit slots per day
                }
            }
        }

        $templateView = 'doctor.landing-page.templates.' . $landingPage->template;

        return view($templateView, compact('landingPage', 'doctor', 'reviews', 'availableSlots', 'blogPosts'))
            ->with('isPreview', false);
    }

    /**
     * Show all blogs for a doctor
     */
    public function showBlogs($username)
    {
        $landingPage = DoctorLandingPage::with(['doctor.user', 'doctor.specialty'])
            ->byUsername($username)
            ->published()
            ->firstOrFail();

        $blogPosts = $landingPage->doctor->publishedBlogPosts()
            ->latest('published_at')
            ->paginate(9);

        // Record visit
        LandingPageVisit::recordVisit($landingPage->doctor->id, request());

        return view('doctor.landing-page.blogs', compact('landingPage', 'blogPosts'));
    }

    /**
     * Show individual blog post
     */
    public function showBlogPost($username, $slug)
    {
        $landingPage = DoctorLandingPage::with(['doctor.user', 'doctor.specialty'])
            ->byUsername($username)
            ->published()
            ->firstOrFail();

        $blogPost = $landingPage->doctor->publishedBlogPosts()
            ->where('slug', $slug)
            ->firstOrFail();

        // Increment views
        $blogPost->incrementViews();

        // Record visit
        LandingPageVisit::recordVisit($landingPage->doctor->id, request());

        // Get related posts
        $relatedPosts = $landingPage->doctor->publishedBlogPosts()
            ->where('id', '!=', $blogPost->id)
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('doctor.landing-page.blog-post', compact('landingPage', 'blogPost', 'relatedPosts'));
    }
}
