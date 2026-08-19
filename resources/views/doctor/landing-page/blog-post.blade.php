<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $blogPost->seo_title }}</title>
    <meta name="description" content="{{ $blogPost->seo_description }}">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $blogPost->seo_title }}">
    <meta property="og:description" content="{{ $blogPost->seo_description }}">
    @if($blogPost->featured_image)
        <meta property="og:image" content="{{ Storage::disk('public')->url($blogPost->featured_image) }}">
    @endif

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="{{ $blogPost->seo_title }}">
    <meta property="twitter:description" content="{{ $blogPost->seo_description }}">
    @if($blogPost->featured_image)
        <meta property="twitter:image" content="{{ Storage::disk('public')->url($blogPost->featured_image) }}">
    @endif

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .blog-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
        }

        .blog-content {
            font-size: 1.1rem;
            line-height: 1.8;
        }

        .blog-content h1, .blog-content h2, .blog-content h3 {
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .blog-content p {
            margin-bottom: 1.5rem;
        }

        .blog-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        .author-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 2rem;
            margin: 3rem 0;
        }

        .related-posts .card {
            transition: transform 0.3s ease;
        }

        .related-posts .card:hover {
            transform: translateY(-5px);
        }

        .back-to-landing {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 25px;
            padding: 8px 20px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-to-landing:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            text-decoration: none;
        }

        .share-buttons .btn {
            border-radius: 50px;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .reading-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(to right, #667eea, #764ba2);
            z-index: 1000;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Reading Progress Bar -->
    <div class="reading-progress" id="reading-progress"></div>

    <!-- Header -->
    <header class="blog-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="mb-3">
                        <a href="{{ route('doctor.landing', $landingPage->username) }}" class="back-to-landing">
                            <i class="fas fa-arrow-left me-2"></i>
                            Back to Dr. {{ $landingPage->doctor->user->name }}'s Profile
                        </a>
                    </div>
                    <h1 class="display-5 fw-bold mb-3">{{ $blogPost->title }}</h1>
                    <p class="lead mb-4">{{ $blogPost->short_description }}</p>
                    <div class="d-flex align-items-center text-white-50">
                        <div class="me-4">
                            <i class="fas fa-calendar me-2"></i>
                            {{ $blogPost->published_at ? $blogPost->published_at->format('F j, Y') : 'Draft' }}
                        </div>
                        <div class="me-4">
                            <i class="fas fa-clock me-2"></i>
                            {{ $blogPost->reading_time }}
                        </div>
                        <div>
                            <i class="fas fa-eye me-2"></i>
                            {{ $blogPost->views_count }} views
                        </div>
                    </div>
                </div>
                @if($blogPost->featured_image)
                    <div class="col-md-4">
                        <img src="{{ Storage::disk('public')->url($blogPost->featured_image) }}"
                             alt="{{ $blogPost->title }}"
                             class="img-fluid rounded shadow">
                    </div>
                @endif
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <!-- Blog Content -->
                    <article class="blog-content">
                        {!! $blogPost->content !!}
                    </article>

                    <!-- Share Buttons -->
                    <div class="share-buttons mt-5 pt-4 border-top">
                        <h5 class="mb-3">Share this article:</h5>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}"
                           target="_blank"
                           class="btn btn-primary">
                            <i class="fab fa-facebook-f me-2"></i>Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($blogPost->title) }}"
                           target="_blank"
                           class="btn btn-info">
                            <i class="fab fa-twitter me-2"></i>Twitter
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(url()->current()) }}"
                           target="_blank"
                           class="btn btn-primary">
                            <i class="fab fa-linkedin-in me-2"></i>LinkedIn
                        </a>
                        <button class="btn btn-secondary" onclick="copyToClipboard()">
                            <i class="fas fa-link me-2"></i>Copy Link
                        </button>
                    </div>

                    <!-- Author Card -->
                    <div class="author-card">
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center">
                                @if($landingPage->doctor->profile_image)
                                    <img src="{{ Storage::disk('public')->url($landingPage->doctor->profile_image) }}"
                                         alt="Dr. {{ $landingPage->doctor->user->name }}"
                                         class="rounded-circle mb-3"
                                         style="width: 100px; height: 100px; object-fit: cover;">
                                @else
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mb-3 mx-auto"
                                         style="width: 100px; height: 100px; font-size: 2rem; font-weight: bold;">
                                        {{ substr($landingPage->doctor->user->name, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-9">
                                <h4 class="mb-2">Dr. {{ $landingPage->doctor->user->name }}</h4>
                                @if($landingPage->doctor->specialty)
                                    <p class="text-muted mb-2">{{ $landingPage->doctor->specialty->name }}</p>
                                @endif
                                @if($landingPage->doctor->bio)
                                    <p class="mb-3">{{ Str::limit($landingPage->doctor->bio, 200) }}</p>
                                @endif
                                <a href="{{ route('doctor.landing', $landingPage->username) }}"
                                   class="btn btn-primary">
                                    <i class="fas fa-user-md me-2"></i>
                                    View Full Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Posts -->
            @if($relatedPosts->count() > 0)
                <div class="row mt-5">
                    <div class="col-12">
                        <h3 class="mb-4 text-center">Related Articles</h3>
                        <div class="row related-posts">
                            @foreach($relatedPosts as $post)
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100 shadow-sm">
                                        @if($post->featured_image)
                                            <img src="{{ Storage::disk('public')->url($post->featured_image) }}"
                                                 class="card-img-top"
                                                 alt="{{ $post->title }}"
                                                 style="height: 200px; object-fit: cover;">
                                        @endif
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title">{{ $post->title }}</h5>
                                            <p class="card-text text-muted">{{ Str::limit($post->short_description, 100) }}</p>
                                            <div class="mt-auto">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        {{ $post->published_at->format('M j, Y') }}
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        {{ $post->reading_time }}
                                                    </small>
                                                </div>
                                                <a href="{{ route('doctor.blog.post', [$landingPage->username, $post->slug]) }}"
                                                   class="btn btn-primary btn-sm w-100">
                                                    Read More <i class="fas fa-arrow-right ms-1"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </main>

    <!-- Chat Widget -->
    @include('components.chat-widget', [
        'doctorUsername' => $landingPage->username,
        'doctorName' => $landingPage->doctor->user->name
    ])

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; {{ date('Y') }} Dr. {{ $landingPage->doctor->user->name }}. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        Powered by
                        <a href="https://medcuraai.com" class="text-white-50 text-decoration-none">
                            <strong>MedCura AI</strong>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            // Reading progress bar
            $(window).scroll(function() {
                const scrollTop = $(window).scrollTop();
                const docHeight = $(document).height() - $(window).height();
                const scrollPercent = (scrollTop / docHeight) * 100;
                $('#reading-progress').css('width', scrollPercent + '%');
            });
        });

        // Copy to clipboard function
        function copyToClipboard() {
            navigator.clipboard.writeText(window.location.href).then(function() {
                // Show success message
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check me-2"></i>Copied!';
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-success');

                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-secondary');
                }, 2000);
            });
        }
    </script>
</body>
</html>
