@extends('layout.default')

@section('content')
    @include('partials.header')
    <h1 class="text-center">{{__('Home Page - theme')}}</h1>

    @push('scripts')
        <script>
            console.log('Скрипт от home.blade.php');
        </script>
    @endpush
@endsection