@extends('layouts.app')

@section('title', 'Добавить мастер-класс')

@section('content')
<div class="row row--nogutter top-line">
    <div class="line"></div>
</div>
<div class="main">
    <div class="row">
        <div class="row--small">
            <form action="{{ route('cabinet.store') }}" method="POST">
                @csrf
                <h2>Форма добавления мастер-класса</h2>
                
                <div class="form-group">
                    <label>Вид творчества <span style="color:#f44336;">*</span></label>
                    <select name="type_id" style="border-color: {{ $errors->has('type_id') ? '#f44336' : '#20416c' }};">
                        <option value="">Выберите вид творчества</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" {{ old('type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('type_id')
                        <div style="color:#f44336; font-size:12px; margin-top:5px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label>Название мастер-класса <span style="color:#f44336;">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" 
                           placeholder="Например: Моделирование транспорта"
                           style="border-color: {{ $errors->has('title') ? '#f44336' : '#20416c' }};">
                    @error('title')
                        <div style="color:#f44336; font-size:12px; margin-top:5px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label>Описание мастер-класса <span style="color:#f44336;">*</span></label>
                    <textarea name="description" 
                              placeholder="Подробно опишите, чему научатся участники..."
                              style="border-color: {{ $errors->has('description') ? '#f44336' : '#20416c' }};">{{ old('description') }}</textarea>
                    @error('description')
                        <div style="color:#f44336; font-size:12px; margin-top:5px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label>Дата <span style="color:#f44336;">*</span></label>
                    <select name="date" id="dateSelect"
                            style="border-color: {{ $errors->has('date') ? '#f44336' : '#20416c' }};">
                        <option value="">Выберите дату</option>
                        @foreach($availableDates as $date)
                            <option value="{{ $date }}" {{ old('date') == $date ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}
                            </option>
                        @endforeach
                    </select>
                    @error('date')
                        <div style="color:#f44336; font-size:12px; margin-top:5px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label>Время (2 часа) <span style="color:#f44336;">*</span></label>
                    <select name="start_time" id="timeSelect"
                            style="border-color: {{ $errors->has('start_time') ? '#f44336' : '#20416c' }};">
                        <option value="">Выберите время</option>
                        @foreach($timeSlots as $slot)
                            @php
                                $slotEnd = \Carbon\Carbon::parse($slot)->addHours(2)->format('H:i');
                                $isBusy = in_array(old('date', '') . '|' . $slot, $busySlots);
                            @endphp
                            @if($isBusy)
                                <option value="{{ $slot }}" disabled style="color:gray; background:#f0f0f0;">
                                    {{ $slot }} - {{ $slotEnd }} (ЗАНЯТО)
                                </option>
                            @else
                                <option value="{{ $slot }}" {{ old('start_time') == $slot ? 'selected' : '' }}>
                                    {{ $slot }} - {{ $slotEnd }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('start_time')
                        <div style="color:#f44336; font-size:12px; margin-top:5px;">{{ $message }}</div>
                    @enderror

                </div>
                
                <div class="form-group">
                    <label>Количество человек в группе <span style="color:#f44336;">*</span></label>
                    <input type="number" name="max_participants" min="1" max="30" 
                           value="{{ old('max_participants', 10) }}"
                           style="border-color: {{ $errors->has('max_participants') ? '#f44336' : '#20416c' }};">
                    @error('max_participants')
                        <div style="color:#f44336; font-size:12px; margin-top:5px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label>Стоимость (руб.) <span style="color:#f44336;">*</span></label>
                    <input type="number" name="price" min="0" step="100" 
                           value="{{ old('price', 1000) }}"
                           style="border-color: {{ $errors->has('price') ? '#f44336' : '#20416c' }};">
                    @error('price')
                        <div style="color:#f44336; font-size:12px; margin-top:5px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <button class="btn">Добавить мастер-класс</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('dateSelect').addEventListener('change', function() {
        var selectedDate = this.value;
        var timeSelect = document.getElementById('timeSelect');
        var busySlots = @json($busySlots);
        
        if (!selectedDate) {
            for (var i = 0; i < timeSelect.options.length; i++) {
                var option = timeSelect.options[i];
                if (option.value) {
                    option.disabled = true;
                    option.style.color = 'gray';
                }
            }
            return;
        }
        
        for (var i = 0; i < timeSelect.options.length; i++) {
            var option = timeSelect.options[i];
            var slotTime = option.value;
            
            if (!slotTime) continue;
            
            var endTime = slotTime === '09:00' ? '11:00' : 
                         (slotTime === '11:00' ? '13:00' : 
                         (slotTime === '13:00' ? '15:00' : '17:00'));
            
            if (busySlots.includes(selectedDate + '|' + slotTime)) {
                option.disabled = true;
                option.style.color = 'gray';
                option.style.background = '#f0f0f0';
                option.text = slotTime + ' - ' + endTime + ' (ЗАНЯТО)';
            } else {
                option.disabled = false;
                option.style.color = '';
                option.style.background = '';
                option.text = slotTime + ' - ' + endTime;
            }
        }
    });
    
    if (document.getElementById('dateSelect').value) {
        document.getElementById('dateSelect').dispatchEvent(new Event('change'));
    }
</script>
@endsection