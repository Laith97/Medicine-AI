@extends('master')

@section('title', 'Contact Us - AI Medical Diagnosis')

@section('content')
<!-- Page Title -->
<section class="page-title page-title-parallax parallax scroll-detect dark page-title-center" style="padding: 140px 0;">
    <img src="demos/medical/images/contact/page-title.jpg" class="parallax-bg">
    <div class="container z-2">
        <div class="page-title-row">
            <div class="page-title-content">
                <div class="emphasis-title dark mb-0">
                    <span class="before-heading text-white fst-italic">support@choosewisely.com</span>
                    <h2 class="fw-bold ls-0 text-white">+1-555-AI-DIAGNOSIS</h2>
                </div>
                <a href="https://maps.google.com/maps?q=Silicon+Valley,+CA,+United+States" data-lightbox="iframe">
                    <i class="bi-map i-large i-plain dark mx-auto"></i>
                </a>
                <span class="fw-semibold ls-1 text-uppercase" style="color: #EEE;">
                    Silicon Valley Innovation Center<br>
                    Palo Alto, CA 94301
                </span>
            </div>
        </div>
    </div>
    <div class="video-overlay z-1" style="background: rgba(222,98,98,0.85);"></div>
</section>

<section id="content">
    <div class="content-wrap">
        <div class="container">
            <div class="row">
                <!-- Contact Form -->
                <div class="col-lg-8">
                    <h3 class="mb-4">Get in Touch with Our Team</h3>
                    <p class="text-muted mb-4">Have questions about our AI diagnosis system? Need technical support? Our team of medical AI experts is here to help.</p>

                    <div class="form-widget">
                        <div class="form-result"></div>
                        <form class="row mb-0" id="template-contactform" name="template-contactform" action="include/form.php" method="post">
                            <div class="form-process">
                                <div class="css3-spinner">
                                    <div class="css3-spinner-scaler"></div>
                                </div>
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="template-contactform-name">Full Name <small>*</small></label>
                                <input type="text" id="template-contactform-name" name="template-contactform-name" value="" class="form-control required">
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="template-contactform-email">Email Address <small>*</small></label>
                                <input type="email" id="template-contactform-email" name="template-contactform-email" value="" class="required email form-control">
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="template-contactform-phone">Phone Number</label>
                                <input type="text" id="template-contactform-phone" name="template-contactform-phone" value="" class="form-control">
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="template-contactform-service">Inquiry Type</label>
                                <select id="template-contactform-service" name="template-contactform-service" class="form-select">
                                    <option value="">-- Select One --</option>
                                    <option value="General Inquiry">General Inquiry</option>
                                    <option value="Technical Support">Technical Support</option>
                                    <option value="Partnership">Partnership Opportunities</option>
                                    <option value="Demo Request">Demo Request</option>
                                    <option value="Billing">Billing & Pricing</option>
                                </select>
                            </div>

                            <div class="col-12 form-group">
                                <label for="template-contactform-subject">Subject <small>*</small></label>
                                <input type="text" id="template-contactform-subject" name="subject" value="" class="required form-control">
                            </div>

                            <div class="col-12 form-group">
                                <label for="template-contactform-message">Message <small>*</small></label>
                                <textarea class="required form-control" id="template-contactform-message" name="template-contactform-message" rows="6" cols="30" placeholder="Tell us about your needs or questions regarding our AI diagnosis system..."></textarea>
                            </div>

                            <div class="col-12 form-group d-none">
                                <input type="text" id="template-contactform-botcheck" name="template-contactform-botcheck" value="" class="form-control">
                            </div>

                            <div class="col-12 form-group">
                                <button class="button button-3d m-0" style="background-color: #DE6262; border-color: #DE6262;" type="submit" id="template-contactform-submit" name="template-contactform-submit" value="submit">
                                    Send Message
                                </button>
                            </div>

                            <input type="hidden" name="prefix" value="template-contactform-">
                        </form>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="col-lg-4">
                    <div style="background-color: #f5f5f5; padding: 30px; border-radius: 10px; margin-bottom: 20px;">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="width: 50px; height: 50px; background-color: #DE6262; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 20px; margin-bottom: 15px;">
                                <i class="fas fa-headset"></i>
                            </div>
                            <h5>24/7 Support</h5>
                            <p class="text-muted">Our AI system is available around the clock, and our support team is here to help.</p>
                            <p><strong>Response Time:</strong> Within 2 hours</p>
                        </div>
                    </div>

                    <div style="background-color: #f5f5f5; padding: 30px; border-radius: 10px; margin-bottom: 20px;">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="width: 50px; height: 50px; background-color: #DE6262; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 20px; margin-bottom: 15px;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h5>HIPAA Compliance</h5>
                            <p class="text-muted">All communications and data are encrypted and HIPAA compliant.</p>
                            <p><strong>Security Level:</strong> Enterprise Grade</p>
                        </div>
                    </div>

                    <div style="background-color: #f5f5f5; padding: 30px; border-radius: 10px;">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="width: 50px; height: 50px; background-color: #DE6262; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 20px; margin-bottom: 15px;">
                                <i class="fas fa-users"></i>
                            </div>
                            <h5>Expert Team</h5>
                            <p class="text-muted">Our team includes medical professionals and AI specialists.</p>
                            <p><strong>Expertise:</strong> Medical AI & Healthcare</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="clear"></div>


    </div>
</section>

@endsection