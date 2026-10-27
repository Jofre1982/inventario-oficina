<div class="row">
    <div class="col-md-8 form-group">
        <label for="name">Nombre *</label>
        <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $dependency->name) }}" maxlength="150" required autofocus placeholder="Ej.: Secretaría de Gobierno">
    </div>
    <div class="col-md-4 form-group">
        <label for="phone">Teléfono / extensión</label>
        <input type="text" class="form-control" name="phone" id="phone" value="{{ old('phone', $dependency->phone) }}" maxlength="60">
    </div>
    <div class="col-md-8 form-group">
        <label for="head_name">Jefe de la dependencia</label>
        <input type="text" class="form-control" name="head_name" id="head_name" value="{{ old('head_name', $dependency->head_name) }}">
        <small class="form-text text-muted">Aparece en el acta de entrega como visto bueno.</small>
    </div>
    <div class="col-md-4 form-group d-flex align-items-center">
        <div class="form-check">
            <label class="form-check-label">
                <input class="form-check-input" type="checkbox" name="active" value="1" @checked(old('active', $dependency->active ?? true))>
                <span class="form-check-sign"><span class="check"></span></span>
                Activa
            </label>
        </div>
    </div>
</div>
