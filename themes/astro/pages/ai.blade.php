@php
        // Декодиране на JSON string в масив
        $h = json_decode($result['horoscope'], true);

        // Ако няма съдържание или е невалиден JSON, празен масив
        if (!$h || !is_array($h)) {
            $h = [];
        }

        // Списък с ключове, които да показваме по ред
        $displayOrder = [
            'date',
            'zodiac_sign',
            'overview',
            'love_and_relationships',
            'career_and_finances',
            'health_and_wellbeing',
            'personal_growth',
            'lucky_numbers',
            'color',
            'advice'
        ];

        // За всеки ключ можем да имаме приятен заглавен текст
        $titles = [
            'date' => __('Date'),
            'zodiac_sign' => __('Zodiac Sign'),
            'overview' => __('General Overview'),
            'love_and_relationships' => __('Love and Relationships'),
            'career_and_finances' => __('Career and Finances'),
            'health_and_wellbeing' => __('Health and Well-being'),
            'personal_growth' => __('Personal Growth'),
            'lucky_numbers' => __('Lucky Numbers'),
            'color' => __('Color'),
            'advice' => __('Advice'),
        ];
    @endphp

    <div class="container my-4">
        @foreach($displayOrder as $key)
            @if(isset($h[$key]))
                <div class="card mb-3">
                    <div class="card-header">{{ $titles[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}</div>
                    <div class="card-body">
                        @if(is_array($h[$key]))
                            {{ implode(', ', $h[$key]) }}
                        @else
                            {{ $h[$key] }}
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
    </div>

