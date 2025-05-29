@extends('master')

@section('title', 'Settings')

@section('content')
		<!-- Page Title
		============================================= -->
		<section class="page-title page-title-parallax parallax scroll-detect dark dark page-title-center" style="padding: 140px 0;">
			<img src="demos/medical/images/contact/page-title.jpg" class="parallax-bg">
			<div class="container z-2">
				<div class="page-title-row">

					<div class="page-title-content">
						<div class="emphasis-title dark mb-0">
							<span class="before-heading text-white fst-italic">medical@canvas.com</span>
							<h2 class="fw-bold ls-0" class="text-white">+1-800-9876-221</h2>
						</div>
						<a href=https://maps.google.com/maps?q=221B+Baker+Street,+London,+United+Kingdom&hl=en&t=v&hnear=221B+Baker+St,+London+NW1+6XE,+United+Kingdom" data-lightbox="iframe"><i class="bi-map i-large i-plain dark mx-auto"></i></a>
						<span class="fw-semibold ls-1 text-uppercase" style="color: #EEE;">795 Folsom Ave, Suite 600<br>San Francisco, CA 94107</span>
					</div>

				</div>
			</div>

			<div class="video-overlay z-1" style="background: rgba(222,98,98,0.85);"></div>
		</section><!-- .page-title end -->
<section id="content">
    <div class="content-wrap">
        <div class="container">

            <div class="row">
                <div class="col-lg-8">

                    <h3>Send us an Email</h3>

                    <div class="form-widget">

                        <div class="form-result"></div>

                        <form class="row mb-0" id="template-contactform" name="template-contactform" action="include/form.php" method="post">

                            <div class="form-process">
                                <div class="css3-spinner">
                                    <div class="css3-spinner-scaler"></div>
                                </div>
                            </div>

                            <div class="col-md-4 form-group">
                                <label for="template-contactform-name">Name <small>*</small></label>
                                <input type="text" id="template-contactform-name" name="template-contactform-name" value="" class="form-control required">
                            </div>

                            <div class="col-md-4 form-group">
                                <label for="template-contactform-email">Email <small>*</small></label>
                                <input type="email" id="template-contactform-email" name="template-contactform-email" value="" class="required email form-control">
                            </div>

                            <div class="col-md-4 form-group">
                                <label for="template-contactform-phone">Phone</label>
                                <input type="text" id="template-contactform-phone" name="template-contactform-phone" value="" class="form-control">
                            </div>

                            <div class="w-100"></div>

                            <div class="col-md-8 form-group">
                                <label for="template-contactform-subject">Subject <small>*</small></label>
                                <input type="text" id="template-contactform-subject" name="subject" value="" class="required form-control">
                            </div>

                            <div class="col-md-4 form-group">
                                <label for="template-contactform-service">Services</label>
                                <select id="template-contactform-service" name="template-contactform-service" class="form-select">
                                    <option value="">-- Select One --</option>
                                    <option value="Wordpress">Wordpress</option>
                                    <option value="PHP / MySQL">PHP / MySQL</option>
                                    <option value="HTML5 / CSS3">HTML5 / CSS3</option>
                                    <option value="Graphic Design">Graphic Design</option>
                                </select>
                            </div>

                            <div class="w-100"></div>

                            <div class="col-12 form-group">
                                <label for="template-contactform-message">Message <small>*</small></label>
                                <textarea class="required form-control" id="template-contactform-message" name="template-contactform-message" rows="6" cols="30"></textarea>
                            </div>

                            <div class="col-12 form-group d-none">
                                <input type="text" id="template-contactform-botcheck" name="template-contactform-botcheck" value="" class="form-control">
                            </div>

                            <div class="col-12 form-group">
                                <button class="button button-3d m-0" type="submit" id="template-contactform-submit" name="template-contactform-submit" value="submit">Send Message</button>
                            </div>

                            <input type="hidden" name="prefix" value="template-contactform-">

                        </form>
                    </div>

                </div>

                <div class="col-lg-4">
                    <div class="opening-table" style="background-color: #f5f5f5">
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

        <div class="clear"></div>

        <div class="row mt-6 footer-stick align-items-stretch">

            <div class="col-lg-4 dark col-padding overflow-hidden" style="background-color: var(--cnvs-themecolor);">
                <div data-animate="fadeInLeft">
                    <a href="#"><h3 class="text-uppercase" style="font-weight: 600;">Health Insurance</h3></a>
                    <p style="line-height: 1.8;">Transform, agency working families thinkers who make change happen communities.&rarr;</p>
                </div>
                <i class="icon-medical-i-cardiology bg-icon"></i>
            </div>

            <div class="col-lg-4 dark col-padding overflow-hidden" style="background-color: #34495e;">
                <div data-animate="fadeInLeft" data-delay="200">
                    <a href="#"><h3 class="text-uppercase" style="font-weight: 600;">Medical Records</h3></a>
                    <p style="line-height: 1.8;">Frontline respond, visionary collaborative cities advancement overcome injustice.&rarr;</p>
                </div>
                <i class="icon-medical-i-administration bg-icon"></i>
            </div>

            <div class="col-lg-4 dark col-padding overflow-hidden" style="background-color: #DE6262;">
                <div data-animate="fadeInLeft" data-delay="400">
                    <a href="#"><h3 class="text-uppercase" style="font-weight: 600;">Online Bill Pay</h3></a>
                    <p style="line-height: 1.8;">Sustainability involvement fundraising campaign connect carbon rights, collaborative cities.&rarr;</p>
                </div>
                <i class="icon-medical-i-billing bg-icon"></i>
            </div>

            <div class="clear"></div>

        </div>
    </div>
</section><!-- #content end -->
@endsection
