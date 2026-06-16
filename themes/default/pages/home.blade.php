@extends('layout.default')

@section('content')
    <h1 class="text-center">{{__('Home Page - theme')}}</h1>
<example-widget/>
    @push('scripts')
        <script>
            console.log('Скрипт от home.blade.php');
        </script>
    @endpush
@endsection