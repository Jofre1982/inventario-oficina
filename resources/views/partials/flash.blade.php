@if (session('success') || session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar"><i class="tim-icons icon-simple-remove"></i></button>
        {{ session('success') ?? session('status') }}
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar"><i class="tim-icons icon-simple-remove"></i></button>
        {{ session('error') }}
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <b>Revise los datos:</b>
        <ul class="mb-0 pl-3">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
