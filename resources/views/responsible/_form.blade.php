<div class="row">
    <div class="col-md-4 form-group">
        <label for="document">Cédula *</label>
        <input type="text" class="form-control" name="document" id="document" value="{{ old('document', $responsible->document) }}" maxlength="30" required autofocus>
    </div>
    <div class="col-md-8 form-group">
        <label for="name">Nombre completo *</label>
        <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $responsible->name) }}" required>
    </div>
    <div class="col-md-6 form-group">
        <label for="position">Cargo</label>
        <input type="text" class="form-control" name="position" id="position" value="{{ old('position', $responsible->position) }}" placeholder="Ej.: Auxiliar administrativo">
    </div>
    <div class="col-md-6 form-group">
        <label for="dependency_id">Dependencia *</label>
        <select class="form-control" name="dependency_id" id="dependency_id" required>
            <option value="">Seleccione...</option>
            @foreach ($dependencies as $dependency)
                <option value="{{ $dependency->id }}" @selected(old('dependency_id', $responsible->dependency_id) == $dependency->id)>{{ $dependency->name }}</option>
            @endforeach
        </select>
        @if ($dependencies->isEmpty())
            <small class="form-text text-warning">Primero cree las <a href="{{ route('dependencies.create') }}">dependencias</a>.</small>
        @endif
    </div>
    <div class="col-md-6 form-group">
        <label for="phone">Teléfono</label>
        <input type="text" class="form-control" name="phone" id="phone" value="{{ old('phone', $responsible->phone) }}" maxlength="60">
    </div>
    <div class="col-md-6 form-group">
        <label for="email">Correo</label>
        <input type="email" class="form-control" name="email" id="email" value="{{ old('email', $responsible->email) }}">
    </div>
    @if ($responsible->exists)
        <div class="col-12 form-group">
            <div class="form-check">
                <label class="form-check-label">
                    <input class="form-check-input" type="checkbox" name="active" value="1" @checked(old('active', $responsible->active))>
                    <span class="form-check-sign"><span class="check"></span></span>
                    Activo (si ya no trabaja en la entidad, desmárquelo; su historial se conserva)
                </label>
            </div>
        </div>
    @endif
</div>
