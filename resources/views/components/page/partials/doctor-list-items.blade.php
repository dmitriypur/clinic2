@foreach ($doctors as $doctor)
    @include('components.page.partials.doctor-card', ['doctor' => $doctor])
@endforeach
