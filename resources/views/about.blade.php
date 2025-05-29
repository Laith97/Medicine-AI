@extends('master')

@section('title', 'Settings')

@section('content')

<section class="page-title dark dark page-title-center p-0">
    <div class="fslider" data-arrows="false" data-pagi="false" data-animation="fade" data-hover="false">
        <div class="flexslider">

            <div class="slider-wrap">
                <div class="slide"><img src="demos/medical/images/about-us/page-title/1.jpg" alt="Page Title Image"></div>
                <div class="slide"><img src="demos/medical/images/about-us/page-title/2.jpg" alt="Page Title Image"></div>
                <div class="slide"><img src="demos/medical/images/about-us/page-title/3.jpg" alt="Page Title Image"></div>
                <div class="slide"><img src="demos/medical/images/about-us/page-title/4.jpg" alt="Page Title Image"></div>
            </div>

            <div class="vertical-middle vertical-middle-overlay">
                <div class="container">
                    <div class="page-title-row">

                        <div class="page-title-content">
                            <h1>About Us</h1>
                            <span>A Short Page Title Tagline</span>
                        </div>

                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">About-us</li>
                            </ol>
                        </nav>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section><!-- .page-title end -->

<!-- Content
============================================= -->
<section id="content">
    <div class="content-wrap">
        <div class="container">

            <div class="row col-mb-50 mb-0">
                <div class="col-sm-6 col-lg-4">
                    <div class="feature-box fbox-plain">
                        <div class="fbox-icon" data-animate="bounceIn">
                            <a href="#"><i class="icon-medical-i-cardiology"></i></a>
                        </div>
                        <div class="fbox-content">
                            <h3>Intensive Care</h3>
                            <p>Powerful Layout with Responsive functionality that can be adapted to any screen size. Resize browser to view.</p>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <div class="feature-box fbox-plain">
                        <div class="fbox-icon" data-animate="bounceIn" data-delay="200">
                            <a href="#"><i class="icon-medical-i-social-services"></i></a>
                        </div>
                        <div class="fbox-content">
                            <h3>Family Planning</h3>
                            <p>Looks beautiful &amp; ultra-sharp on Retina Screen Displays. Retina Icons, Fonts &amp; all others graphics are optimized.</p>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <div class="feature-box fbox-plain">
                        <div class="fbox-icon" data-animate="bounceIn" data-delay="400">
                            <a href="#"><i class="icon-medical-i-neurology"></i></a>
                        </div>
                        <div class="fbox-content">
                            <h3>Expert Consultation</h3>
                            <p>Canvas includes tons of optimized code that are completely customizable and deliver unmatched fast performance.</p>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <div class="feature-box fbox-plain">
                        <div class="fbox-icon" data-animate="bounceIn">
                            <a href="#"><i class="icon-medical-i-dental"></i></a>
                        </div>
                        <div class="fbox-content">
                            <h3>Dental Sciences</h3>
                            <p>Powerful Layout with Responsive functionality that can be adapted to any screen size. Resize browser to view.</p>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <div class="feature-box fbox-plain">
                        <div class="fbox-icon" data-animate="bounceIn" data-delay="200">
                            <a href="#"><i class="icon-medical-i-imaging-root-category"></i></a>
                        </div>
                        <div class="fbox-content">
                            <h3>X-Ray Services</h3>
                            <p>Looks beautiful &amp; ultra-sharp on Retina Screen Displays. Retina Icons, Fonts &amp; all others graphics are optimized.</p>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4">
                    <div class="feature-box fbox-plain">
                        <div class="fbox-icon" data-animate="bounceIn" data-delay="400">
                            <a href="#"><i class="icon-medical-i-ambulance"></i></a>
                        </div>
                        <div class="fbox-content">
                            <h3>24x7 Emergency</h3>
                            <p>Canvas includes tons of optimized code that are completely customizable and deliver unmatched fast performance.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="row counters mb-0 mt-4 align-items-stretch">

            <div class="col-lg-3 col-md-6 dark text-center min-vh-40" style="background: url('demos/medical/images/about-us/counters/1.jpg') no-repeat center center; background-size: cover;">
                <div class="bg-overlay">
                    <div class="bg-overlay-content dark">
                        <div>
                            <i class="i-plain i-xlarge mx-auto icon-medical-i-surgery"></i>
                            <div class="counter counter-lined"><span data-from="100" data-to="42762" data-refresh-interval="50" data-speed="2300"></span></div>
                            <h5>of Treatments Made</h5>
                        </div>
                    </div>
                    <div class="bg-overlay-bg op-ts op-1" data-hover-animate="op-0" data-hover-animate-out="op-1" style="background-color: rgba(229, 57, 53, 0.8);"></div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 dark text-center min-vh-40" style="background: url('demos/medical/images/about-us/counters/2.jpg') no-repeat center center; background-size: cover;">
                <div class="bg-overlay">
                    <div class="bg-overlay-content dark">
                        <div>
                            <i class="i-plain i-xlarge mx-auto icon-medical-i-respiratory"></i>
                            <div class="counter counter-lined"><span data-from="3000" data-to="21500" data-refresh-interval="100" data-speed="2500"></span></div>
                            <h5>Cured Patients</h5>
                        </div>
                    </div>
                    <div class="bg-overlay-bg op-ts op-1" data-hover-animate="op-0" data-hover-animate-out="op-1" style="background-color: rgba(211, 47, 47, 0.8);"></div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 dark text-center min-vh-40" style="background: url('demos/medical/images/about-us/counters/3.jpg') no-repeat center center; background-size: cover;">
                <div class="bg-overlay">
                    <div class="bg-overlay-content dark">
                        <div>
                            <i class="i-plain i-xlarge mx-auto icon-medical-i-social-services"></i>
                            <div class="counter counter-lined"><span data-from="20" data-to="408" data-refresh-interval="25" data-speed="3500"></span>K</div>
                            <h5>Satisfied Customers</h5>
                        </div>
                    </div>
                    <div class="bg-overlay-bg op-ts op-1" data-hover-animate="op-0" data-hover-animate-out="op-1" style="background-color: rgba(198, 40, 40, 0.8);"></div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 dark text-center min-vh-40" style="background: url('demos/medical/images/about-us/counters/4.jpg') no-repeat center center; background-size: cover;">
                <div class="bg-overlay">
                    <div class="bg-overlay-content dark">
                        <div>
                            <i class="i-plain i-xlarge mx-auto icon-medical-i-ambulance"></i>
                            <div class="counter counter-lined"><span data-from="60" data-to="140" data-refresh-interval="20" data-speed="2700"></span></div>
                            <h5>Ambulance Available</h5>
                        </div>
                    </div>
                    <div class="bg-overlay-bg op-ts op-1" data-hover-animate="op-0" data-hover-animate-out="op-1" style="background-color: rgba(183, 28, 28, 0.8);"></div>
                </div>
            </div>

        </div>

        <div class="section mt-0" style="background: #FFF url('demos/medical/images/about-us/1.jpg') right center no-repeat / cover;">

            <div class="d-block d-md-block d-lg-none" style="background-color: rgba(238,238,238,0.9); position: absolute; top: 0;left: 0; z-index: 1;width: 100%; height: 100%;"></div>

            <div class="container">

                <div class="row justify-content-between">
                    <div class="col-lg-6" data-lightbox="gallery">
                        <div class="heading-block border-bottom-0 mb-4">
                            <h3 class="text-transform-none ls-0">What We do Actually</h3>
                        </div>
                        <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Consectetur necessitatibus placeat numquam enim adipisci nostrum facilis distinctio modi, cupiditate laborum ea eius repellendus? Obcaecati saepe numquam pariatur aliquid, aspernatur necessitatibus dolores harum quos eum esse, laudantium odit alias, iste dolorem!</p>
                        <div class="feature-box fbox-plain fbox-sm mb-4">
                            <div class="fbox-icon mt-2" data-animate="fadeIn">
                                <a href="#"><i class="icon-medical-i-womens-health"></i></a>
                            </div>
                            <div class="fbox-content">
                                <p class="mt-0">Powerful Layout with Responsive functionality that can be adapted to any screen size. Resize browser to view.</p>
                            </div>
                        </div>

                        <div class="feature-box fbox-plain fbox-sm mb-4">
                            <div class="fbox-icon mt-2" data-animate="fadeIn">
                                <a href="#"><i class="icon-medical-i-ultrasound"></i></a>
                            </div>
                            <div class="fbox-content">
                                <p class="mt-0">Powerful Layout with Responsive functionality that can be adapted to any screen size. Resize browser to view.</p>
                            </div>
                        </div>

                        <div class="feature-box fbox-plain fbox-sm mb-4">
                            <div class="fbox-icon mt-2" data-animate="fadeIn">
                                <a href="#"><i class="icon-medical-i-cath-lab"></i></a>
                            </div>
                            <div class="fbox-content">
                                <p class="mt-0">Powerful Layout with Responsive functionality that can be adapted to any screen size. Resize browser to view.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="opening-table">
                            <div class="heading-block mb-4 border-bottom-0">
                                <h4>Opening Hours</h4>
                                <span>Lorem ipsum dolor sit amet, consecte adipisicing elit molestiae non.</span>
                            </div>
                            <div class="time-table-wrap">
                                <div class="row time-table">
                                    <h5 class="col-lg-5">Monday</h5>
                                    <span class="col-lg-7">8:00am - 11:00pm</span>
                                </div>
                                <div class="row time-table">
                                    <h5 class="col-lg-5">Tuesday</h5>
                                    <span class="col-lg-7">8:00am - 11:00pm</span>
                                </div>
                                <div class="row time-table">
                                    <h5 class="col-lg-5">Wednesday</h5>
                                    <span class="col-lg-7">8:00am - 11:00pm</span>
                                </div>
                                <div class="row time-table">
                                    <h5 class="col-lg-5">Thursday</h5>
                                    <span class="col-lg-7">8:00am - 11:00pm</span>
                                </div>
                                <div class="row time-table">
                                    <h5 class="col-lg-5">Friday</h5>
                                    <span class="col-lg-7">8:00am - 11:00pm</span>
                                </div>
                                <div class="row time-table">
                                    <h5 class="col-lg-5">Saturday</h5>
                                    <span class="col-lg-7">8:00am - 1:30pm</span>
                                </div>
                                <div class="row time-table">
                                    <h5 class="col-lg-5">Sunday</h5>
                                    <span class="col-lg-7 color fw-semibold">Closed</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <div class="container">
            <div class="heading-block text-center border-bottom-0">
                <h3>Meet our Team of Specialists<span>.</span></h3>
                <span>We make sure that your Life are in Good Hands.</span>
            </div>

            <div id="oc-team" class="owl-carousel team-carousel carousel-widget" data-margin="30" data-nav="true" data-pagi="true" data-items-xs="1" data-items-sm="2" data-items-lg="3" data-items-xl="4">

                <div class="team">
                    <div class="team-image">
                        <img src="demos/medical/images/doctors/1.jpg" alt="Dr. John Doe">
                    </div>
                    <div class="team-desc">
                        <div class="team-title"><h4>Dr. John Doe</h4><span>Cardiologist</span></div>
                    </div>
                </div>

                <div class="team">
                    <div class="team-image">
                        <img src="demos/medical/images/doctors/2.jpg" alt="Dr. John Doe">
                    </div>
                    <div class="team-desc">
                        <div class="team-title"><h4>Dr. Bryan Mcguire</h4><span>Orthopedist</span></div>
                    </div>
                </div>

                <div class="team">
                    <div class="team-image">
                        <img src="demos/medical/images/doctors/3.jpg" alt="Dr. John Doe">
                    </div>
                    <div class="team-desc">
                        <div class="team-title"><h4>Dr. Mary Jane</h4><span>Neurologist</span></div>
                    </div>
                </div>

                <div class="team">
                    <div class="team-image">
                        <img src="demos/medical/images/doctors/4.jpg" alt="Dr. John Doe">
                    </div>
                    <div class="team-desc">
                        <div class="team-title"><h4>Dr. Silvia Bush</h4><span>Dentist</span></div>
                    </div>
                </div>

                <div class="team">
                    <div class="team-image">
                        <img src="demos/medical/images/doctors/6.jpg" alt="Dr. John Doe">
                    </div>
                    <div class="team-desc">
                        <div class="team-title"><h4>Dr. Hugh Baldwin</h4><span>Cardiologist</span></div>
                    </div>
                </div>

                <div class="team">
                    <div class="team-image">
                        <img src="demos/medical/images/doctors/7.jpg" alt="Dr. John Doe">
                    </div>
                    <div class="team-desc">
                        <div class="team-title"><h4>Dr. Erika Todd</h4><span>Neurologist</span></div>
                    </div>
                </div>

                <div class="team">
                    <div class="team-image">
                        <img src="demos/medical/images/doctors/8.jpg" alt="Dr. John Doe">
                    </div>
                    <div class="team-desc">
                        <div class="team-title"><h4>Dr. Randy Adams</h4><span>Dentist</span></div>
                    </div>
                </div>

                <div class="team">
                    <div class="team-image">
                        <img src="demos/medical/images/doctors/9.jpg" alt="Dr. John Doe">
                    </div>
                    <div class="team-desc">
                        <div class="team-title"><h4>Dr. Alan Freeman</h4><span>Eye Specialist</span></div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</section><!-- #content end -->

@endsection
