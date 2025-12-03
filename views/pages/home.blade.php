@extends('layout.default')
@section('content')
    <h1 class="text-center">Default Home Page - when no theme !</h1>
    @push('scripts')
        <script>
            console.log('Скрипт от views.home.blade.php');
        </script>
    @endpush
@endsection