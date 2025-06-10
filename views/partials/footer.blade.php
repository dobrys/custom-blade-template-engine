<!-- Footer Area -->
@php
$show_newsletter=true;

 @endphp
<div class="footer-area">
    <div class="container ptb-100">
        @if($show_newsletter)
        <div class="newsletter-area" id="newsletter">
            <div class="section-title text-center">
                <span>{{ __('JOIN THE COMMUNITY') }}</span>
                <h2 class="m-auto">{{ __('Subscribe Our Newsletter')  }}</h2>
            </div>
            <form class="newsletter-form" method="post" action="{{ $site }} api.php?action=newsletter">
                <input type="email" class="form-control" placeholder="Enter Your Email Address *" name="email" required autocomplete="off">
                <input class="subscribe-btn" name="subscribe" type="submit" value="Subscribe Now">
            </form>
        </div>
        @endif
        <div style="text-align:center;">

            <div style="color:#ffffff;margin-top:10px;">
                <p>MBX Productive Ltd Pimen Zogravski Nr. 14 1000 Sofia,</p>
                <p>Bulgaria</p>
            </div>
        </div>
    </div>

    <div class="copyright-area">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-md-5">
                    <ul class="copyright-list">
                        <li>
                            <a href="{{ $site }}about/terms-and-conditions.php">
                                {{ __('Terms and conditions')  }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ $site }}about/privacy-policy.php">
                                {{ __('Privacy policy');  }}
                                {{ echo('sadas'); }}
                                {{var_dump(3);}}
                            </a>
                        </li>
                        <li>
                            <a href="{{ $site }}#contact-us">
                                {{__('Contact us');}}
                            </a>
                        </li>

                        <li>
                            <a href="{{ $site }}assets/docs/RO_Imprint.docx">
                                    {{ __('Imprint');  }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ $site }}assets/docs/RO_unsubscription.docx">
                                    {{ __('Gestionarea Abonamentului');  }}
                            </a>
                        </li>


                    </ul>
                </div>
                <div class="col-lg-6 col-md-7">
                    <div class="copy-right-text">
                        <p>© 2015 - 2025 DailyFit24.com</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Footer Area End -->


<!-- Jquery Min JS -->
<script src="{{ $site }}assets/js/jquery.min.js"></script>
<!-- Plugins JS -->
<script src="{{ $site }}assets/js/plugins.js"></script>
<!-- Custom  JS -->
<script src="{{ $site }}assets/js/custom.js?v={{ time();  }}"></script>

<script type="text/javascript">
    function toggleMenu() {
        var x = document.getElementById("menu-mob");
        if (x.style.display === "block") {
            x.style.display = "none";
        } else {
            x.style.display = "block";
        }
    }
    function toogleMyWorkouts() {
        var x = document.getElementById("menu-my-workouts");
        if (x.style.display === "block") {
            x.style.display = "none";
        } else {
            x.style.display = "block";
        }
    }
    document.addEventListener('contextmenu', event => event.preventDefault());

    let prevLink = document.getElementById("prevLink");
    let nextLink = document.getElementById("nextLink");
    document.addEventListener("keydown", ({key}) => {
        switch (key) {
            case 'ArrowLeft':
                console.log('Left arrow');
                prevLink.click();
                break;
            case 'ArrowRight':
                console.log('Right arrow');
                nextLink.click();
                break;
        }
    });
</script>