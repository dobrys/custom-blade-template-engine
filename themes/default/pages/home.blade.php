@extends('layout.default')
@section('content')
    <h1 class="text-center">Home Page</h1>
    @push('scripts')
        <script>
            console.log('Скрипт от home.blade.php');
        </script>
    @endpush
@endsection