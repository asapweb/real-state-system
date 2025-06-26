<?php

namespace App\Http\Controllers;

use App\Events\VisitsCall;
use App\Events\VisitsNew;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    public function notificationAppontmentStatus($id, Request $request) {
        $anAppointment = Appointment::with(['client', 'department', 'creator', 'updator'])->withTrashed()->find($id);
            $socketId = $request->header('X-Socket-Id');
            // return VisitsNew::dispatch($anAppointment)->toOthers($socketId);

            broadcast(new VisitsNew($anAppointment, $socketId));


    }
    public function notificationCall(Request $request) {
        $data = Appointment::with(['client', 'department'])->find( $request->appointment_id);
        if ($data) {
            $client = $data->client->name;
            VisitsCall::dispatch($client, $data->department->location);
        }
    }

    /**
     * Display the specified resource.
     */
    public function status(Appointment $appointment, Request $request)
    {
        // return response()->json(auth()->user()->name);

        switch ($request->status) {
            case 'attended':
                $appointment->updated_by = auth()->id();
                $appointment->attended_start_at = now();
                $appointment->attended_start_by = auth()->id();
                $appointment->status = 'attended';
                $appointment->save();
                $this->notificationAppontmentStatus($appointment->id, $request);
                return $appointment;
                break;

            case 'seen':
                $appointment->updated_by = auth()->id();
                $appointment->attended_end_at = now();
                $appointment->attended_end_by = auth()->id();
                $appointment->status = 'seen';
                $appointment->save();
                $this->notificationAppontmentStatus($appointment->id, $request);
                return $appointment;
                break;

                default:
                # code...
                return 'else';
                break;
        }
        return $request;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Appointment::query();

        // Ordenamiento
        if ($request->has('sortBy')) {
            foreach ($request->sortBy as $sort) {
                $query->orderBy($sort['key'], $sort['order']);
            }
        }
        // Filtros
        if ($request->has('search.text')) {
            // $query->where('id', $request->search['status']);
            $query->whereHas('client', function ($q) use ($request) {
                $q->where('name',  'like', '%'.$request->search['text'] .'%')
                ->orWhere('last_name',  'like', '%'.$request->search['text'] .'%');
            });
        }

        // Registros de hoy y anteriores que no se finalizaron
        if ($request->has('search.isDashboard')) {
            $horaInicio = Carbon::now()->startOfDay();
            $query->where(function ($q) use($horaInicio){
                $q->where('created_at', '>=', $horaInicio)
                ->orwhereNull('attended_end_at');
            });
        }

        if ($request->has('search.department_id')) {
            $query->whereIn('department_id', $request->search['department_id']);
        }

        if ($request->has('search.status')) {
            $query->where('status', $request->search['status']);
            switch ($request->search['status']) {
                case 'arrived':
                    $query->orderBy('received_at', 'asc');
                    break;
                case 'attended':
                    $query->orderBy('attended_start_at', 'asc');
                    break;
                case 'seen':
                    $query->orderBy('attended_end_at', 'asc');
                    break;

                    default:
                    break;
            }
        }


         // Paginación
         $appointments = $query->with(['client', 'department', 'employee', 'creator.client','receiver.client', 'attendantStart.client', 'attendantEnd.client'])->paginate($request->get('itemsPerPage', 10));
         return response()->json($appointments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'client_id' => 'required',
            'department_id' => 'sometimes|required',
            'employee_id' => '',
        ]);

        $appointment = new Appointment();
        $appointment->is_unexpected = 1;
        switch ($request->status) {
            case 'booked':
                $appointment->start_at = now();
                $appointment->booked_by = auth()->id();
                $appointment->is_unexpected = 0;
                break;
            case 'arrived':
                $appointment->received_at = now();
                $appointment->received_by = auth()->id();
                break;
            case 'attended':
                $appointment->attended_start_at = now();
                $appointment->attended_start_by = auth()->id();
                break;
            case 'seen':
                $appointment->attended_end_at = now();
                $appointment->attended_end_by = auth()->id();
                break;

            default:
                # code...
                break;
        }

        $appointment->client_id = $request->client_id;
        $appointment->employee_id = $request->employee_id;
        $appointment->department_id = $request->department_id;
        $appointment->notes = $request->notes;
        $appointment->status = $request->status;
        $appointment->created_by = auth()->id();
        $appointment->updated_by = auth()->id();
        $appointment->save();

        if (in_array($request->status, ['arrived', 'attended', 'seen'])) {
            $this->notificationAppontmentStatus($appointment->id, $request);
        }

        return [ 'appointment' => $appointment];
    }

    /**
     * Display the specified resource.
     */
    public function show(Appointment $appointment)
    {
        return $appointment->load(['client', 'department', 'employee', 'creator.client','receiver.client', 'attendantStart.client', 'attendantEnd.client']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Appointment $appointment)
    {
        $fields = $request->validate([
            'client_id' => 'required',
            'department_id' => 'sometimes|required',
            'employee_id' => '',
        ]);

        $appointment->client_id = $request->client_id;
        $appointment->employee_id = $request->employee_id;
        $appointment->department_id = $request->department_id;
        $appointment->notes = $request->notes;
        $appointment->updated_by = auth()->id();
        $appointment->save();

        if (in_array($request->status, ['arrived', 'attended', 'seen'])) {
            $this->notificationAppontmentStatus($appointment->id, $request);
        }

        return [ 'appointment' => $appointment];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Appointment $appointment, Request $request)
    {
        try {
            $appointment_id = $appointment->id;
            // return auth()->id();
            DB::beginTransaction();
            $appointment->deleted_by = auth()->id();
            $appointment->status = 'deleted';
            $appointment->save();
            $appointment->delete();
            DB::commit();
            $this->notificationAppontmentStatus($appointment->id, $request);
            return response()->json(['message' => 'Appointment marcado como eliminado exitosamente']);
        } catch (\Exception $e) {
            // Revierte la transacción en caso de error
            DB::rollBack();

            // Maneja el error (por ejemplo, loguea el error o muestra un mensaje al usuario)
            \Log::error($e); // Loguea el error para depuración
            return response()->json(['message' => 'Error al eliminar el registro'], 500); // Devuelve un error 500
        }
    }
}
