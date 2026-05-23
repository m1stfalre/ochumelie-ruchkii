<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterClass;
use App\Models\CreativityType;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InstructorController extends Controller
{
    public function index()
    {
        $classes = MasterClass::where('instructor_id', Auth::id())
            ->with(['bookings.user', 'type'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return view('cabinet', compact('classes'));
    }

    public function create()
    {
        $types = CreativityType::all();
        
        // Получаем занятые слоты для текущего ведущего
        $busySlots = MasterClass::where('instructor_id', Auth::id())
            ->where('date', '>=', Carbon::now()->startOfDay())
            ->get(['date', 'start_time'])
            ->map(function($item) {
                return $item->date->format('Y-m-d') . '|' . $item->start_time;
            })
            ->toArray();
        
        $availableDates = [];
        for ($i = 0; $i <= 30; $i++) {
            $date = Carbon::now()->addDays($i);
            if (!$date->isWeekend()) {
                $availableDates[] = $date->format('Y-m-d');
            }
        }
        
        $timeSlots = ['09:00', '11:00', '13:00', '15:00'];
        
        return view('create', compact('types', 'busySlots', 'availableDates', 'timeSlots'));
    }

    public function store(Request $request)
    {
        // Валидация полей 
        $validated = $request->validate([
            'type_id' => ['required', 'exists:creativity_types,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'in:09:00,11:00,13:00,15:00'],
            'max_participants' => ['required', 'integer', 'min:1', 'max:30'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000'],
        ], [
            // Сообщения для type_id
            'type_id.required' => 'Пожалуйста, выберите вид творчества.',
            'type_id.exists' => 'Выбранный вид творчества не существует.',
            
            // Сообщения для title
            'title.required' => 'Введите название мастер-класса.',
            'title.max' => 'Название мастер-класса не может быть длиннее 255 символов.',
            
            // Сообщения для description
            'description.required' => 'Введите описание мастер-класса.',
            'description.min' => 'Описание должно содержать минимум 10 символов.',
            
            // Сообщения для date
            'date.required' => 'Выберите дату проведения мастер-класса.',
            'date.after_or_equal' => 'Дата не может быть раньше сегодняшнего дня.',
            
            // Сообщения для start_time
            'start_time.required' => 'Выберите время проведения мастер-класса.',
            'start_time.in' => 'Время должно быть 9:00, 11:00, 13:00 или 15:00.',
            
            // Сообщения для max_participants
            'max_participants.required' => 'Укажите максимальное количество участников.',
            'max_participants.min' => 'Минимальное количество участников - 1 человек.',
            'max_participants.max' => 'Максимальное количество участников - 30 человек.',
            
            // Сообщения для price
            'price.required' => 'Укажите стоимость мастер-класса.',
            'price.min' => 'Стоимость не может быть отрицательной.',
            'price.max' => 'Стоимость не может превышать 100 000 ₽.',
        ]);

        $isBusy = MasterClass::where('instructor_id', Auth::id())
            ->where('date', $validated['date'])
            ->where('start_time', $validated['start_time'])
            ->exists();

        if ($isBusy) {
            return back()
                ->withErrors([
                    'start_time' => 'У вас уже запланирован мастер-класс на эту дату и время. Выберите другую дату или время.'
                ])
                ->withInput();
        }


        $selectedDate = Carbon::parse($validated['date']);
        if ($selectedDate->isPast() && !$selectedDate->isToday()) {
            return back()
                ->withErrors([
                    'date' => 'Нельзя создать мастер-класс на прошедшую дату.'
                ])
                ->withInput();
        }


        $classesCountOnDate = MasterClass::where('instructor_id', Auth::id())
            ->where('date', $validated['date'])
            ->count();
        
        if ($classesCountOnDate >= 3) {
            return back()
                ->withErrors([
                    'date' => 'Вы не можете провести более 3 мастер-классов в один день.'
                ])
                ->withInput();
        }

        $timeConflict = MasterClass::where('instructor_id', Auth::id())
            ->where('date', $validated['date'])
            ->where(function($query) use ($validated) {

                $query->where('start_time', $validated['start_time']);
            })
            ->exists();

        if ($timeConflict) {
            return back()
                ->withErrors([
                    'start_time' => 'Это время уже занято другим мастер-классом в выбранную дату.'
                ])
                ->withInput();
        }

        MasterClass::create([
            'instructor_id' => Auth::id(),
            ...$validated,
        ]);

        return redirect()->route('cabinet.index')
            ->with('message', 'Мастер-класс "' . $validated['title'] . '" успешно добавлен!');
    }


    public function edit($id)
    {
        $masterClass = MasterClass::where('instructor_id', Auth::id())->findOrFail($id);
        return view('edit', compact('masterClass'));
    }

    public function update(Request $request, $id)
    {
        $masterClass = MasterClass::where('instructor_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'description' => ['required', 'string', 'min:10'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000'],
        ], [
            'description.required' => 'Введите описание мастер-класса.',
            'description.min' => 'Описание должно содержать минимум 10 символов.',
            'price.required' => 'Укажите стоимость мастер-класса.',
            'price.min' => 'Стоимость не может быть отрицательной.',
            'price.max' => 'Стоимость не может превышать 100 000 ₽.',
        ]);

        $masterClass->update($validated);

        return redirect()->route('cabinet.index')
            ->with('message', 'Мастер-класс "' . $masterClass->title . '" успешно обновлен!');
    }
    
    public function destroy($id)
    {
        $masterClass = MasterClass::where('instructor_id', Auth::id())->findOrFail($id);
        
        if ($masterClass->bookings()->count() > 0) {
            return back()->with('error', 'Нельзя удалить мастер-класс, на который уже есть записи.');
        }
        
        $title = $masterClass->title;
        $masterClass->delete();
        
        return redirect()->route('cabinet.index')
            ->with('message', 'Мастер-класс "' . $title . '" успешно удален!');
    }
}