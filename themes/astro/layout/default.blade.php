<!DOCTYPE html>
<html lang="{{$site_language}}" dir="{{$text_direction}}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{$brand }} - {{__(' Premium Astrology Subscriptions') }}</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #0b0f1a;
            --accent: #c9a24d;
            --muted: #9ca3af;
            --text-light: #f5f5f5;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        h1, h2, h3, h4, h5 {
            font-family: 'Playfair Display', serif;
        }
        .hero {
            min-height: 100vh;
        }
        .badge-ai {
            background-color: rgba(201, 162, 77, 0.15);
            color: var(--accent);
            border: 1px solid rgba(201, 162, 77, 0.4);
        }
        .btn-accent {
            background-color: var(--accent);
            color: #000;
            border: none;
        }
        .btn-accent:hover {
            background-color: #b8943f;
        }
        .card {
            background-color: #11162a;
            border: 1px solid rgba(255,255,255,0.05);
            color: var(--text-light);
        }
        .astro-card{
            color: var(--accent);;
        }
        .price {
            font-size: 3rem;
            font-weight: 700;
            color: var(--accent);
        }
        .text-muted-custom {
            color: var(--muted);
        }
        .login-container {
            flex: 1 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 0;
        }
        .login-card {
            background-color: #11162a;
            border: 1px solid rgba(255,255,255,0.05);
            padding: 2rem;
            border-radius: 1rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }
        .form-control {
            background-color: #0b0f1a;
            border: 1px solid rgba(201,162,77,0.3);
            color: #fff;
        }
        .form-control:focus {
            border-color: #c9a24d;
            box-shadow: 0 0 0 0.2rem rgba(201,162,77,0.25);
        }
        .btn-accent {
            background-color: #c9a24d;
            color: #000;
            border: none;
        }
        .btn-accent:hover {
            background-color: #b8943f;
        }
        .text-muted-custom {
            color: #9ca3af;
        }
        a {
            color: #c9a24d;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        footer {
            border-top: 1px solid rgba(255,255,255,0.05);
            padding: 1rem 0;
            text-align: center;
            margin-top: auto;
        }
        .astro-color{
            color: #b8943f;

        }
        .login-input::placeholder {
            color: #b8943f;       /* бял текст */
            opacity: 1;        /* да не е прозрачен */
        }
        .input-group-text i {
            color: #b8943f !important;  /* белият цвят ще се приложи на иконата */
        }
        .input-group-text {
            color: #b8943f !important;        /* бяла икона */
            background-color: transparent;
            border-color: rgba(201,162,77,0.3);

        }
    </style>


</head>
<body>

{{--@dump($site_language,$text_direction)--}}

<main>
    @yield('content')
</main>
@include('partials.footer')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
