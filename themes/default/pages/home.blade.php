@extends('layout.default')

@section('content')
    @include('partials.header')
    <h1 class="text-center">Home Page</h1>
    @dump($_SESSION)
    @dump($is_logged_in)
    @push('scripts')
        <script>
            console.log('Скрипт от home.blade.php');
        </script>
    @endpush
@endsection