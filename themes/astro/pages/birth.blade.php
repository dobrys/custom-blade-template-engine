@extends('layout.default')
@section('content')
    @include('partials.header')

    <div class="container py-5 d-flex flex-column min-vh-100">
        <div class="row justify-content-center flex-grow-1 align-items-center">
            <div class="col-md-6">
                <div class="card astro-card mt-4">
                    <div class="card-body">
                        <h4 class="mb-3 text-center">{{ __('Birth Analysis') }}</h4>

                        <form id="birthForm">
                            <div class="mb-3">
                                <label for="birth_date" class="form-label">{{ __('Date of Birth') }}</label>
                                <input type="date" class="form-control" id="birth_date" name="birth_date" value="1974-01-20" required>
                            </div>

                            <div class="mb-3">
                                <label for="birth_time" class="form-label">{{ __('Time of Birth (optional)') }}</label>
                                <input type="time" class="form-control" id="birth_time" name="birth_time">
                            </div>

                            <div class="mb-3">
                                <label for="birth_place" class="form-label">{{ __('Place of Birth (optional)') }}</label>
                                <input type="text" class="form-control" id="birth_place" name="birth_place" placeholder="{{ __('Sofia, Bulgaria') }}">
                            </div>

                            <!-- Езиково падащо меню -->
                            <div class="mb-3">
                                <label for="birth_language" class="form-label">{{ __('Language') }}</label>
                                <select class="form-select" id="birth_language" name="birth_language">
                                    <option value="en">{{ __('English') }}</option>
                                    <option value="bg">{{ __('Bulgarian') }}</option>
                                    <option value="de">{{ __('German') }}</option>
                                    <option value="fr">{{ __('French') }}</option>
                                    <option value="es">{{ __('Spanish') }}</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-accent w-100">{{ __('Star Analysis') }}</button>
                        </form>

                    </div>
                </div>
            </div>

            <div id="aiResult" class="mt-3"></div>

            <!-- Красив анимиран loader -->
            <div id="loader" class="mt-4 text-center" style="display:none;">
                <div class="spinner">
                    <div></div><div></div><div></div><div></div>
                </div>
                <div class="mt-2 text-muted-custom">{{ __('Generating your horoscope...') }}</div>
            </div>
        </div>

    </div>

    <style>
        /* Spinner анимация */
        .spinner {
            display: inline-block;
            position: relative;
            width: 50px;
            height: 50px;
        }
        .spinner div {
            box-sizing: border-box;
            display: block;
            position: absolute;
            width: 40px;
            height: 40px;
            margin: 4px;
            border: 4px solid var(--accent);
            border-radius: 50%;
            animation: spinner 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
            border-color: var(--accent) transparent transparent transparent;
        }
        .spinner div:nth-child(1) { animation-delay: -0.45s; }
        .spinner div:nth-child(2) { animation-delay: -0.3s; }
        .spinner div:nth-child(3) { animation-delay: -0.15s; }

        @keyframes spinner {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    @push('scripts')
        <script>
            document.getElementById('birthForm').addEventListener('submit', function(e){
                e.preventDefault();

                const aiResult = document.getElementById('aiResult');
                const loader = document.getElementById('loader');
                const cacheLoaderTime = 2; // секунди за симулиран loader

                aiResult.innerHTML = '';
                loader.style.display = 'block';

                let formData = new FormData(this);

                fetch('/hscope.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if(data.cache) {
                            // Симулиран loader за кеширан хороскоп
                            setTimeout(() => {
                                loader.style.display = 'none';
                                aiResult.innerHTML = data.result;
                            }, cacheLoaderTime * 1000);
                        } else {
                            // Реален loader за нов хороскоп
                            loader.style.display = 'none';
                            aiResult.innerHTML = data.result;
                        }
                    })
                    .catch(err => {
                        loader.style.display = 'none';
                        aiResult.innerHTML = '<div class="text-danger">Error fetching analysis.</div>';
                        console.error(err);
                    });
            });
        </script>
    @endpush
@endsection
