<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Articles by Dr. {{ $landingPage->doctor->user->name }}</title>
    <meta name="description" content="Read health tips and medical articles by Dr. {{ $landingPage->doctor->user->name }}">

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
            padding: 80px 0;
        }

        .blog-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            height: 100%;
        }

        .blog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .blog-card img {
            height: 200px;
            object-fit: cover;
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

        .pagination {
            justify-content: center;
        }

        .page-link {
            border-radius: 50px;
            margin: 0 5px;
            border: none;
            color: #667eea;
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="blog-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="mb-3">
                        <a href="{{ route('doctor.landing', $landingPage->username) }}" class="back-to-landing">
                            <i class="fas fa-arrow-left me-2"></i>
                            Back to Dr. {{ $landingPage->doctor->user->name }}'s Profile
                        </a>
                    </div>
                    <h1 class="display-4 fw-bold mb-3">Health Articles</h1>
                    <p class="lead mb-4">Expert medical advice and health tips from Dr. {{ $landingPage->doctor->user->name }}</p>
                    <div class="d-flex align-items-center text-white-50">
                        <div class="me-4">
                            <i class="fas fa-user-md me-2"></i>
                            {{ $landingPage->doctor->specialty->name ?? 'Medical Professional' }}
                        </div>
                        <div>
                            <i class="fas fa-newspaper me-2"></i>
                            {{ $blogPosts->total() }} Articles
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="py-5">
        <div class="container">
            @if($blogPosts->count() > 0)
                <div class="row g-4">
                    @foreach($blogPosts as $post)
                        <div class="col-lg-4 col-md-6">
                            <div class="card blog-card shadow-sm">
                                @if($post->featured_image)
                                    <img src="{{ Storage::url($post->featured_image) }}" 
                                         class="card-img-top" 
                                         alt="{{ $post->title }}">
                                @endif
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fw-bold">{{ $post->title }}</h5>
                                    <p class="card-text text-muted flex-grow-1">
                                        {{ Str::limit($post->short_description, 120) }}
                                    </p>
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
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-eye me-1"></i>
                                                {{ $post->views_count }} views
                                            </small>
                                            <a href="{{ route('doctor.blog.post', [$landingPage->username, $post->slug]) }}" 
                                               class="btn btn-primary btn-sm">
                                                Read More <i class="fas fa-arrow-right ms-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($blogPosts->hasPages())
                    <div class="row mt-5">
                        <div class="col-12">
                            {{ $blogPosts->links() }}
                        </div>
                    </div>
                @endif
            @else
                <div class="row">
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-newspaper fa-4x text-muted mb-4"></i>
                        <h3 class="text-muted mb-3">No Articles Yet</h3>
                        <p class="text-muted">Dr. {{ $landingPage->doctor->user->name }} hasn't published any articles yet. Check back soon!</p>
                        <a href="{{ route('doctor.landing', $landingPage->username) }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Profile
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </main>

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
</body>
</html>