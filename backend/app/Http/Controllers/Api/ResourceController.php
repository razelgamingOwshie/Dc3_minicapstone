<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use App\Models\Vaccination;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResourceController extends Controller
{
    private array $map = [
        'pets' => Pet::class,
        'services' => Service::class,
        'appointments' => Appointment::class,
        'medical-records' => MedicalRecord::class,
        'vaccinations' => Vaccination::class,
        'owners' => User::class,
    ];

    private function model(Request $request): string
    {
        $resource = $request->route('resource');
        abort_unless(isset($this->map[$resource]), 404);
        return $this->map[$resource];
    }

    private function isAdmin(Request $request): bool
    {
        return $request->user()?->role === 'admin';
    }

    private function scoped(Builder $query, Request $request, string $resource): Builder
    {
        if ($this->isAdmin($request)) {
            return $query;
        }

        return match ($resource) {
            'services' => $query->where('is_available', true),
            'pets' => $query->where('user_id', $request->user()->id),
            'appointments' => $query->where('user_id', $request->user()->id),
            'medical-records', 'vaccinations' => $query->whereHas(
                'pet', fn (Builder $pets) => $pets->where('user_id', $request->user()->id)
            ),
            'owners' => abort(403, 'Administrator access is required.'),
            default => $query,
        };
    }

    private function rules(string $resource, ?int $id = null): array
    {
        $petOwnerId = Rule::exists('users', 'id')->where('role', 'pet_owner');

        return match ($resource) {
            'pets' => [
                'user_id' => ['sometimes', 'integer', $petOwnerId],
                'name' => 'required|string|max:120',
                'species' => 'required|string|max:60',
                'breed' => 'nullable|string|max:100',
                'sex' => ['nullable', Rule::in(['Male', 'Female', 'Unknown'])],
                'birth_date' => 'nullable|date|before_or_equal:today',
                'weight' => 'nullable|numeric|min:0|max:99999',
                'notes' => 'nullable|string|max:5000',
            ],
            'services' => [
                'name' => 'required|string|max:120',
                'description' => 'nullable|string|max:5000',
                'price' => 'required|numeric|min:0|max:99999999',
                'duration_minutes' => 'required|integer|min:5|max:600',
                'is_available' => 'sometimes|boolean',
            ],
            'appointments' => [
                'user_id' => ['sometimes', 'integer', $petOwnerId],
                'pet_id' => 'required|integer|exists:pets,id',
                'service_id' => 'required|integer|exists:services,id',
                'appointment_date' => 'required|date|after:now',
                'reason' => 'nullable|string|max:3000',
                'status' => ['sometimes', Rule::in(['pending', 'approved', 'completed', 'cancelled'])],
            ],
            'medical-records' => [
                'pet_id' => 'required|integer|exists:pets,id',
                'appointment_id' => 'nullable|integer|exists:appointments,id',
                'diagnosis' => 'required|string|max:5000',
                'treatment' => 'nullable|string|max:5000',
                'notes' => 'nullable|string|max:5000',
                'record_date' => 'required|date|before_or_equal:today',
            ],
            'vaccinations' => [
                'pet_id' => 'required|integer|exists:pets,id',
                'vaccine_name' => 'required|string|max:120',
                'date_administered' => 'nullable|date|before_or_equal:today',
                'next_due_date' => 'nullable|date',
                'notes' => 'nullable|string|max:5000',
            ],
            'owners' => [
                'name' => 'required|string|max:120',
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            ],
            default => [],
        };
    }

    private function eagerLoads(string $resource): array
    {
        return match ($resource) {
            'pets' => ['owner:id,name,email'],
            'appointments' => ['owner:id,name,email', 'pet', 'service'],
            'medical-records' => ['pet.owner:id,name,email', 'appointment.service'],
            'vaccinations' => ['pet.owner:id,name,email'],
            default => [],
        };
    }

    public function index(Request $request)
    {
        $resource = (string) $request->route('resource');
        $model = $this->model($request);
        $query = $this->scoped($model::query(), $request, $resource);

        if ($resource === 'owners') {
            $query->where('role', 'pet_owner');
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function (Builder $where) use ($search, $resource) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
                match ($resource) {
                    'pets' => $where->where('name', 'like', $like)
                        ->orWhere('species', 'like', $like)
                        ->orWhere('breed', 'like', $like)
                        ->orWhereHas('owner', fn (Builder $owner) => $owner->where('name', 'like', $like)->orWhere('email', 'like', $like)),
                    'services' => $where->where('name', 'like', $like)->orWhere('description', 'like', $like),
                    'owners' => $where->where('name', 'like', $like)->orWhere('email', 'like', $like),
                    'appointments' => $where->where('reason', 'like', $like)
                        ->orWhereHas('owner', fn (Builder $owner) => $owner->where('name', 'like', $like))
                        ->orWhereHas('pet', fn (Builder $pet) => $pet->where('name', 'like', $like))
                        ->orWhereHas('service', fn (Builder $service) => $service->where('name', 'like', $like)),
                    'medical-records' => $where->where('diagnosis', 'like', $like)
                        ->orWhere('treatment', 'like', $like)
                        ->orWhereHas('pet', fn (Builder $pet) => $pet->where('name', 'like', $like)),
                    'vaccinations' => $where->where('vaccine_name', 'like', $like)
                        ->orWhereHas('pet', fn (Builder $pet) => $pet->where('name', 'like', $like)),
                    default => $where,
                };
            });
        }

        if ($resource === 'appointments') {
            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }
            if ($request->filled('date')) {
                $request->validate(['date' => 'date_format:Y-m-d']);
                $query->whereDate('appointment_date', $request->query('date'));
            }
        }

        if ($resource === 'services' && $request->filled('available')) {
            $query->where('is_available', $request->boolean('available'));
        }

        if ($resource === 'vaccinations' && $request->filled('due')) {
            match ($request->query('due')) {
                'overdue' => $query->whereNotNull('next_due_date')->whereDate('next_due_date', '<', today()),
                'soon' => $query->whereBetween('next_due_date', [today(), today()->copy()->addDays(30)]),
                'later' => $query->whereDate('next_due_date', '>', today()->copy()->addDays(30)),
                default => abort(422, 'Invalid vaccination due filter.'),
            };
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 10)));
        return response()->json($query->with($this->eagerLoads($resource))->latest()->paginate($perPage));
    }

    public function store(Request $request)
    {
        $resource = (string) $request->route('resource');
        $model = $this->model($request);
        $admin = $this->isAdmin($request);

        abort_if($resource === 'owners', 403, 'Use the controlled registration or administrator account process.');
        abort_if(!$admin && !in_array($resource, ['pets', 'appointments'], true), 403);

        $data = $request->validate($this->rules($resource));

        if ($resource === 'pets') {
            if ($admin) {
                if (empty($data['user_id'])) {
                    abort(422, 'Select a pet owner for this pet.');
                }
            } else {
                $data['user_id'] = $request->user()->id;
            }
        }

        if ($resource === 'appointments') {
            $pet = Pet::findOrFail($data['pet_id']);
            abort_if(!$admin && (int) $pet->user_id !== (int) $request->user()->id, 403, 'That pet does not belong to you.');
            if ($admin && isset($data['user_id']) && (int) $data['user_id'] !== (int) $pet->user_id) {
                abort(422, 'The selected owner must match the selected pet owner.');
            }
            $data['user_id'] = $pet->user_id;
            $data['status'] = 'pending';
            $service = Service::findOrFail($data['service_id']);
            abort_unless($service->is_available, 422, 'This service is not currently available.');
            $this->assertNoConflict($data);
        }

        if (in_array($resource, ['medical-records', 'vaccinations'], true)) {
            abort_unless($admin, 403, 'Only clinic staff can create clinical records.');
            Pet::findOrFail($data['pet_id']);
            if ($resource === 'medical-records' && !empty($data['appointment_id'])) {
                $appointment = Appointment::findOrFail($data['appointment_id']);
                abort_unless((int) $appointment->pet_id === (int) $data['pet_id'], 422, 'The selected appointment does not belong to this pet.');
                abort_unless($appointment->status === 'completed', 422, 'A medical record can only be attached to a completed appointment.');
            }
        }

        if ($resource === 'vaccinations') {
            $this->assertVaccinationDates($data);
        }

        $item = $model::create($data);
        return response()->json(['data' => $item->fresh()->load($this->eagerLoads($resource))], 201);
    }

    public function show(Request $request)
    {
        $resource = (string) $request->route('resource');
        $item = $this->scoped(($this->model($request))::query(), $request, $resource)
            ->with($this->eagerLoads($resource))->findOrFail($request->route('id'));
        return response()->json(['data' => $item]);
    }

    public function update(Request $request)
    {
        $resource = (string) $request->route('resource');
        $model = $this->model($request);
        $admin = $this->isAdmin($request);

        if (!$admin && !in_array($resource, ['pets', 'appointments'], true)) {
            abort(403);
        }

        $item = $this->scoped($model::query(), $request, $resource)->findOrFail($request->route('id'));

        // Pet owners may only cancel their own pending appointments; they cannot reschedule or reassign them.
        if ($resource === 'appointments' && !$admin) {
            abort_unless($item->status === 'pending', 403, 'Only pending appointments can be cancelled by an owner.');
            $status = $request->input('status');
            if (in_array($status, ['approved', 'completed'], true)) {
                abort(403, 'Pet owners may only cancel their own pending appointments.');
            }
            $request->validate(['status' => ['required', Rule::in(['cancelled'])]]);
            $item->update(['status' => 'cancelled']);
            return response()->json(['data' => $item->fresh()->load($this->eagerLoads($resource))]);
        }

        $rules = $this->rules($resource, (int) $item->id);
        foreach ($rules as $field => $rule) {
            $parts = is_array($rule) ? $rule : explode('|', $rule);
            $parts = array_values(array_filter($parts, fn ($part) => $part !== 'required'));
            if (!in_array('sometimes', $parts, true)) {
                array_unshift($parts, 'sometimes');
            }
            $rules[$field] = $parts;
        }
        $data = $request->validate($rules);

        if ($resource === 'pets') {
            unset($data['user_id']); // ownership cannot be changed by editing a pet
        }

        if ($resource === 'appointments') {
            if (in_array($item->status, ['completed', 'cancelled'], true)
                && (isset($data['appointment_date']) || isset($data['pet_id']) || isset($data['service_id']))) {
                abort(422, 'Completed or cancelled appointments cannot be rescheduled.');
            }
            $this->assertStatusTransition($request, $item, $data);
            $petId = (int) ($data['pet_id'] ?? $item->pet_id);
            $pet = Pet::findOrFail($petId);
            if (isset($data['user_id']) && (int) $data['user_id'] !== (int) $pet->user_id) {
                abort(422, 'The appointment owner must match the selected pet owner.');
            }
            $data['user_id'] = $pet->user_id;
            $serviceId = (int) ($data['service_id'] ?? $item->service_id);
            $service = Service::findOrFail($serviceId);
            if ($serviceId !== (int) $item->service_id || isset($data['appointment_date'])) {
                abort_unless($service->is_available, 422, 'This service is not currently available for new or rescheduled appointments.');
            }
            if (isset($data['appointment_date']) || isset($data['service_id']) || isset($data['pet_id'])) {
                $this->assertNoConflict(array_merge($item->toArray(), $data), (int) $item->id);
            }
        }

        if ($resource === 'medical-records') {
            $petId = (int) ($data['pet_id'] ?? $item->pet_id);
            Pet::findOrFail($petId);
            $appointmentId = $data['appointment_id'] ?? $item->appointment_id;
            if ($appointmentId) {
                $appointment = Appointment::findOrFail($appointmentId);
                abort_unless((int) $appointment->pet_id === $petId, 422, 'The selected appointment does not belong to this pet.');
                abort_unless($appointment->status === 'completed', 422, 'Medical records can only be attached to completed appointments.');
            }
        }

        if ($resource === 'vaccinations') {
            Pet::findOrFail($data['pet_id'] ?? $item->pet_id);
            $this->assertVaccinationDates(array_merge($item->toArray(), $data));
        }

        if ($resource === 'owners') {
            abort_unless($admin, 403);
            unset($data['role']);
        }

        $item->update($data);
        return response()->json(['data' => $item->fresh()->load($this->eagerLoads($resource))]);
    }

    public function destroy(Request $request)
    {
        $resource = (string) $request->route('resource');
        $admin = $this->isAdmin($request);
        abort_unless($admin || $resource === 'pets', 403);
        $item = $this->scoped(($this->model($request))::query(), $request, $resource)->findOrFail($request->route('id'));

        if ($resource === 'pets' && ($item->appointments()->exists() || $item->medicalRecords()->exists() || $item->vaccinations()->exists())) {
            abort(422, 'This pet has appointment or medical history. Keep the record to preserve its history.');
        }
        if ($resource === 'owners' && ($item->pets()->exists() || $item->appointments()->exists())) {
            abort(422, 'This owner has linked clinic records. Deactivate or retain the account instead of deleting historical data.');
        }
        if ($resource === 'appointments' && $item->medicalRecord()->exists()) {
            abort(422, 'This appointment has a medical record and cannot be deleted.');
        }

        try {
            $item->delete();
        } catch (QueryException) {
            abort(422, 'This record is linked to other clinic records and cannot be deleted.');
        }

        return response()->json(['message' => 'Record deleted.']);
    }

    private function assertStatusTransition(Request $request, Appointment $appointment, array $data): void
    {
        if (!isset($data['status'])) {
            return;
        }
        $next = $data['status'];
        $allowed = [
            'pending' => ['approved', 'cancelled'],
            'approved' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];
        abort_unless(in_array($next, $allowed[$appointment->status] ?? [], true), 422, 'Invalid appointment status transition.');
    }

    private function assertVaccinationDates(array $data): void
    {
        if (!empty($data['date_administered']) && !empty($data['next_due_date'])) {
            abort_if(
                Carbon::parse($data['next_due_date'])->lt(Carbon::parse($data['date_administered'])),
                422,
                'The next due date cannot be earlier than the administration date.'
            );
        }
    }

    private function assertNoConflict(array $data, ?int $ignoreId = null): void
    {
        $start = Carbon::parse($data['appointment_date']);
        $service = Service::findOrFail($data['service_id']);
        $end = $start->copy()->addMinutes((int) $service->duration_minutes);

        $query = Appointment::whereIn('status', ['pending', 'approved'])->with('service');
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        foreach ($query->get() as $other) {
            if (!$other->service) {
                continue;
            }
            $otherStart = Carbon::parse($other->appointment_date);
            $otherEnd = $otherStart->copy()->addMinutes((int) $other->service->duration_minutes);
            if ($start->lt($otherEnd) && $end->gt($otherStart)) {
                abort(422, 'That time conflicts with an existing appointment. Choose another time.');
            }
        }
    }

    public function dashboard(Request $request)
    {
        abort_unless($this->isAdmin($request), 403, 'Administrator access is required.');

        return response()->json([
            'total_pets' => Pet::count(),
            'total_pet_owners' => User::where('role', 'pet_owner')->count(),
            'active_services' => Service::where('is_available', true)->count(),
            'appointments_today' => Appointment::whereDate('appointment_date', today())->count(),
            'pending_appointments' => Appointment::where('status', 'pending')->count(),
            'completed_consultations' => Appointment::where('status', 'completed')->count(),
            'upcoming_vaccinations' => Vaccination::whereNotNull('next_due_date')
                ->whereBetween('next_due_date', [today(), today()->copy()->addDays(30)])->count(),
        ]);
    }
}
