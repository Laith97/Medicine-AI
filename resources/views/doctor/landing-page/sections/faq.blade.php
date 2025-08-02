@php
$config = $section['config'] ?? [];
$isBuilder = $isBuilder ?? false;
$faqs = $config['faqs'] ?? [
    ['question' => 'How do I book an appointment?', 'answer' => 'You can book an appointment online through our website or by calling our office directly. We offer flexible scheduling to accommodate your needs.'],
    ['question' => 'What insurance plans do you accept?', 'answer' => 'We accept most major insurance plans including Blue Cross Blue Shield, Aetna, Cigna, and Medicare. Please contact our office to verify your specific coverage.'],
    ['question' => 'What should I bring to my first appointment?', 'answer' => 'Please bring a valid ID, your insurance card, a list of current medications, and any relevant medical records or test results from previous doctors.'],
    ['question' => 'How long is a typical appointment?', 'answer' => 'Initial consultations typically last 45-60 minutes, while follow-up appointments are usually 15-30 minutes depending on your needs.'],
    ['question' => 'Do you offer telemedicine consultations?', 'answer' => 'Yes, we offer secure video consultations for certain types of appointments. This is especially convenient for follow-ups and routine check-ins.'],
    ['question' => 'What is your cancellation policy?', 'answer' => 'We require at least 24 hours notice for appointment cancellations. Late cancellations or no-shows may be subject to a fee.']
];
@endphp

<section class="faq-section py-5 {{ $isBuilder ? 'builder-section' : '' }}"
         data-section-id="{{ $section['id'] ?? '' }}"
         style="background-color: {{ $config['background_color'] ?? '#f8fafc' }};"
         @if(isset($config['animation']) && $config['animation'] && !$isBuilder)
         data-aos="{{ $config['animation'] }}"
         data-aos-duration="1000"
         @endif>

    <div class="container">
        <!-- Section Header -->
        <div class="row">
            <div class="col-lg-8 mx-auto text-center mb-5">
                <h2 class="section-title h1 fw-bold mb-3"
                    style="color: {{ $config['text_color'] ?? '#374151' }};">
                    {{ $config['title'] ?? 'Frequently Asked Questions' }}
                </h2>

                @if(isset($config['subtitle']) && $config['subtitle'])
                <p class="section-subtitle lead text-muted">
                    {{ $config['subtitle'] }}
                </p>
                @endif
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                @if(($config['layout'] ?? 'accordion') === 'accordion')
                <!-- Accordion Layout -->
                <div class="faq-accordion">
                    <div class="accordion" id="faqAccordion">
                        @foreach($faqs as $index => $faq)
                        <div class="accordion-item faq-item"
                             @if(!$isBuilder)
                             data-aos="fade-up"
                             data-aos-delay="{{ $index * 100 }}"
                             @endif>
                            <h3 class="accordion-header" id="faqHeading{{ $index }}">
                                <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#faqCollapse{{ $index }}"
                                        aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                                        aria-controls="faqCollapse{{ $index }}">
                                    <div class="faq-question-wrapper d-flex align-items-center w-100">
                                        <div class="faq-icon me-3">
                                            <i class="fas fa-question-circle text-primary"></i>
                                        </div>
                                        <div class="faq-question-text">
                                            {{ $faq['question'] }}
                                        </div>
                                    </div>
                                </button>
                            </h3>
                            <div id="faqCollapse{{ $index }}"
                                 class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                                 aria-labelledby="faqHeading{{ $index }}"
                                 data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <div class="faq-answer d-flex">
                                        <div class="answer-icon me-3 mt-1">
                                            <i class="fas fa-check-circle text-success"></i>
                                        </div>
                                        <div class="answer-text">
                                            <p class="mb-0">{{ $faq['answer'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                @else
                <!-- Card Layout -->
                <div class="faq-cards row g-4">
                    @foreach($faqs as $index => $faq)
                    <div class="col-lg-6">
                        <div class="faq-card h-100 p-4 rounded-3 shadow-sm bg-white border"
                             @if(!$isBuilder)
                             data-aos="fade-up"
                             data-aos-delay="{{ $index * 100 }}"
                             @endif>
                            <div class="faq-card-header d-flex align-items-start mb-3">
                                <div class="faq-icon me-3">
                                    <div class="icon-wrapper d-flex align-items-center justify-content-center rounded-circle"
                                         style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));">
                                        <i class="fas fa-question text-white"></i>
                                    </div>
                                </div>
                                <div class="faq-question flex-grow-1">
                                    <h4 class="h6 fw-bold mb-0" style="color: {{ $config['text_color'] ?? '#374151' }};">
                                        {{ $faq['question'] }}
                                    </h4>
                                </div>
                            </div>
                            <div class="faq-answer">
                                <p class="text-muted mb-0">{{ $faq['answer'] }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                <!-- Search FAQ -->
                @if(isset($config['show_search']) && $config['show_search'])
                <div class="faq-search mt-5"
                     @if(!$isBuilder)
                     data-aos="fade-up"
                     data-aos-delay="600"
                     @endif>
                    <div class="search-wrapper bg-white p-4 rounded-3 shadow-sm border">
                        <h4 class="h5 fw-bold mb-3 text-center">Can't find what you're looking for?</h4>
                        <div class="search-input-group position-relative">
                            <input type="text"
                                   class="form-control form-control-lg rounded-pill ps-5"
                                   id="faqSearch"
                                   placeholder="Search frequently asked questions...">
                            <div class="search-icon position-absolute top-50 start-0 translate-middle-y ms-3">
                                <i class="fas fa-search text-muted"></i>
                            </div>
                        </div>
                        <div class="search-results mt-3" id="searchResults" style="display: none;">
                            <!-- Search results will be populated here -->
                        </div>
                    </div>
                </div>
                @endif

                <!-- Contact CTA -->
                @if(isset($config['show_contact_cta']) && $config['show_contact_cta'])
                <div class="faq-contact-cta mt-5"
                     @if(!$isBuilder)
                     data-aos="fade-up"
                     data-aos-delay="700"
                     @endif>
                    <div class="cta-wrapper text-center p-5 rounded-4"
                         style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(16, 185, 129, 0.1)); border: 1px solid rgba(59, 130, 246, 0.2);">
                        <div class="cta-icon mb-3">
                            <i class="fas fa-headset fa-3x text-primary"></i>
                        </div>
                        <h4 class="fw-bold mb-3" style="color: {{ $config['text_color'] ?? '#374151' }};">
                            Still have questions?
                        </h4>
                        <p class="text-muted mb-4">
                            Our friendly team is here to help. Get in touch and we'll get back to you as soon as possible.
                        </p>
                        <div class="cta-buttons">
                            <a href="{{ $config['contact_link'] ?? '#contact' }}"
                               class="btn btn-primary btn-lg rounded-pill me-3 mb-2">
                                <i class="fas fa-envelope me-2"></i>
                                Contact Us
                            </a>
                            @if(isset($config['phone']) && $config['phone'])
                            <a href="tel:{{ $config['phone'] }}"
                               class="btn btn-outline-primary btn-lg rounded-pill mb-2">
                                <i class="fas fa-phone me-2"></i>
                                {{ $config['phone'] }}
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- FAQ Categories -->
                @if(isset($config['show_categories']) && $config['show_categories'])
                <div class="faq-categories mt-5"
                     @if(!$isBuilder)
                     data-aos="fade-up"
                     data-aos-delay="800"
                     @endif>
                    <h4 class="h5 fw-bold mb-4 text-center">Browse by Category</h4>
                    <div class="row g-3">
                        @foreach(($config['categories'] ?? [
                            ['name' => 'Appointments', 'icon' => 'fas fa-calendar-alt', 'count' => 5],
                            ['name' => 'Insurance', 'icon' => 'fas fa-shield-alt', 'count' => 3],
                            ['name' => 'Services', 'icon' => 'fas fa-stethoscope', 'count' => 4],
                            ['name' => 'Billing', 'icon' => 'fas fa-credit-card', 'count' => 2]
                        ]) as $category)
                        <div class="col-lg-3 col-md-6">
                            <div class="category-card p-3 rounded-3 border text-center h-100 category-filter-btn"
                                 data-category="{{ strtolower($category['name']) }}"
                                 style="cursor: pointer; transition: all 0.3s ease;">
                                <div class="category-icon mb-2">
                                    <i class="{{ $category['icon'] }} fa-2x text-primary"></i>
                                </div>
                                <h6 class="category-name fw-bold mb-1">{{ $category['name'] }}</h6>
                                <small class="text-muted">{{ $category['count'] }} questions</small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>

@if(!$isBuilder)
<style>
.faq-section {
    position: relative;
    overflow: hidden;
}

.faq-section::before {
    content: '';
    position: absolute;
    bottom: 20%;
    left: -5%;
    width: 150px;
    height: 150px;
    background: linear-gradient(45deg, var(--primary-color, #3b82f6), var(--accent-color, #10b981));
    border-radius: 50%;
    opacity: 0.05;
    z-index: 0;
}

/* Accordion Styles */
.faq-accordion {
    position: relative;
    z-index: 1;
}

.faq-item {
    border: 1px solid #e2e8f0;
    border-radius: 12px !important;
    margin-bottom: 1rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.faq-item:hover {
    border-color: var(--primary-color, #3b82f6);
    box-shadow: 0 5px 15px rgba(59, 130, 246, 0.1);
}

.accordion-button {
    background: white;
    border: none;
    padding: 1.5rem;
    font-weight: 600;
    color: #374151;
    box-shadow: none;
    border-radius: 12px !important;
}

.accordion-button:not(.collapsed) {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(16, 185, 129, 0.05));
    color: var(--primary-color, #3b82f6);
    border-bottom: 1px solid #e2e8f0;
    border-radius: 12px 12px 0 0 !important;
}

.accordion-button:focus {
    box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
}

.accordion-button::after {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%233b82f6'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
    transform: scale(1.2);
    transition: transform 0.3s ease;
}

.accordion-button:not(.collapsed)::after {
    transform: scale(1.2) rotate(180deg);
}

.accordion-body {
    padding: 1.5rem;
    background: white;
}

.faq-question-wrapper {
    text-align: left;
}

.faq-icon {
    flex-shrink: 0;
}

.answer-icon {
    flex-shrink: 0;
}

/* Card Layout Styles */
.faq-card {
    transition: all 0.3s ease;
    border-color: #e2e8f0;
}

.faq-card:hover {
    border-color: var(--primary-color, #3b82f6);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.1) !important;
    transform: translateY(-5px);
}

.icon-wrapper {
    position: relative;
    overflow: hidden;
}

.icon-wrapper::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transform: rotate(45deg);
    transition: all 0.6s ease;
    opacity: 0;
}

.faq-card:hover .icon-wrapper::before {
    opacity: 1;
    animation: shimmer 1.5s ease-in-out;
}

@keyframes shimmer {
    0% {
        transform: translateX(-100%) translateY(-100%) rotate(45deg);
    }
    100% {
        transform: translateX(100%) translateY(100%) rotate(45deg);
    }
}

/* Search Styles */
.search-wrapper {
    border-color: #e2e8f0;
    transition: all 0.3s ease;
}

.search-wrapper:hover {
    border-color: var(--primary-color, #3b82f6);
    box-shadow: 0 5px 15px rgba(59, 130, 246, 0.1);
}

.search-input-group input:focus {
    border-color: var(--primary-color, #3b82f6);
    box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
}

.search-result-item {
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-result-item:hover {
    border-color: var(--primary-color, #3b82f6);
    background: rgba(59, 130, 246, 0.05);
}

/* Category Styles */
.category-card {
    border-color: #e2e8f0;
    transition: all 0.3s ease;
}

.category-card:hover {
    border-color: var(--primary-color, #3b82f6);
    background: rgba(59, 130, 246, 0.05) !important;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(59, 130, 246, 0.1);
}

.category-card.active {
    border-color: var(--primary-color, #3b82f6);
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(16, 185, 129, 0.1)) !important;
}

/* CTA Styles */
.faq-contact-cta .cta-wrapper {
    transition: all 0.3s ease;
}

.faq-contact-cta .cta-wrapper:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(59, 130, 246, 0.15);
}

/* Responsive Design */
@media (max-width: 768px) {
    .accordion-button {
        padding: 1rem;
        font-size: 0.9rem;
    }

    .accordion-body {
        padding: 1rem;
    }

    .faq-card {
        margin-bottom: 1.5rem;
    }

    .faq-question-wrapper {
        flex-direction: column;
        align-items: flex-start;
    }

    .faq-icon {
        margin-bottom: 0.5rem;
        margin-right: 0;
    }

    .cta-buttons .btn {
        display: block;
        width: 100%;
        margin-bottom: 1rem;
    }
}

/* Accessibility */
.accordion-button:focus,
.faq-card:focus,
.category-card:focus {
    outline: 2px solid var(--primary-color, #3b82f6);
    outline-offset: 2px;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .faq-item,
    .faq-card,
    .category-card {
        border-width: 2px;
    }

    .accordion-button:not(.collapsed) {
        background: #f0f9ff;
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .faq-item,
    .faq-card,
    .category-card,
    .accordion-button::after {
        transition: none;
    }

    .faq-card:hover,
    .category-card:hover {
        transform: none;
    }
}

/* Print Styles */
@media print {
    .faq-search,
    .faq-contact-cta,
    .faq-categories {
        display: none;
    }

    .accordion-collapse {
        display: block !important;
    }

    .accordion-button::after {
        display: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // FAQ Search functionality
    const searchInput = document.getElementById('faqSearch');
    const searchResults = document.getElementById('searchResults');
    const faqItems = document.querySelectorAll('.faq-item, .faq-card');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();

            if (query.length > 0) {
                searchFAQs(query);
                searchResults.style.display = 'block';
            } else {
                searchResults.style.display = 'none';
                showAllFAQs();
            }
        });
    }

    function searchFAQs(query) {
        let hasResults = false;
        searchResults.innerHTML = '';

        faqItems.forEach((item, index) => {
            const question = item.querySelector('.faq-question-text, .faq-question h4')?.textContent.toLowerCase();
            const answer = item.querySelector('.faq-answer p, .answer-text p')?.textContent.toLowerCase();

            if (question?.includes(query) || answer?.includes(query)) {
                hasResults = true;

                // Create search result item
                const resultItem = document.createElement('div');
                resultItem.className = 'search-result-item';
                resultItem.innerHTML = `
                    <h6 class="fw-bold mb-2">${highlightText(question, query)}</h6>
                    <p class="mb-0 text-muted small">${highlightText(answer?.substring(0, 100) + '...', query)}</p>
                `;

                resultItem.addEventListener('click', function() {
                    scrollToFAQ(index);
                    searchInput.value = '';
                    searchResults.style.display = 'none';
                });

                searchResults.appendChild(resultItem);
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });

        if (!hasResults) {
            searchResults.innerHTML = `
                <div class="text-center p-4">
                    <i class="fas fa-search fa-2x text-muted mb-3"></i>
                    <p class="text-muted mb-0">No results found for "${query}"</p>
                </div>
            `;
        }
    }

    function highlightText(text, query) {
        if (!text || !query) return text;
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }

    function showAllFAQs() {
        faqItems.forEach(item => {
            item.style.display = 'block';
        });
    }

    function scrollToFAQ(index) {
        const faqItem = faqItems[index];
        if (faqItem) {
            faqItem.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Open accordion if it's collapsed
            const accordionButton = faqItem.querySelector('.accordion-button');
            if (accordionButton && accordionButton.classList.contains('collapsed')) {
                accordionButton.click();
            }

            // Highlight the item temporarily
            faqItem.style.background = 'rgba(59, 130, 246, 0.1)';
            setTimeout(() => {
                faqItem.style.background = '';
            }, 2000);
        }
    }

    // Category filtering
    const categoryButtons = document.querySelectorAll('.category-filter-btn');
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.dataset.category;

            // Update active state
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            // Filter FAQs (this would need to be implemented based on your data structure)
            filterFAQsByCategory(category);
        });
    });

    function filterFAQsByCategory(category) {
        // This is a placeholder - you would implement actual filtering logic
        // based on how you categorize your FAQs
        console.log('Filtering by category:', category);
    }

    // Keyboard navigation for accordion
    document.addEventListener('keydown', function(e) {
        if (e.target.classList.contains('accordion-button')) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                const buttons = Array.from(document.querySelectorAll('.accordion-button'));
                const currentIndex = buttons.indexOf(e.target);
                let nextIndex;

                if (e.key === 'ArrowDown') {
                    nextIndex = (currentIndex + 1) % buttons.length;
                } else {
                    nextIndex = (currentIndex - 1 + buttons.length) % buttons.length;
                }

                buttons[nextIndex].focus();
            }
        }
    });

    // Analytics tracking for FAQ interactions
    document.querySelectorAll('.accordion-button').forEach(button => {
        button.addEventListener('click', function() {
            const question = this.querySelector('.faq-question-text')?.textContent;
            // Track FAQ interaction
            if (typeof gtag !== 'undefined') {
                gtag('event', 'faq_interaction', {
                    'event_category': 'FAQ',
                    'event_label': question
                });
            }
        });
    });
});
</script>
@endif
