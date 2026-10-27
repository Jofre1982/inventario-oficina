<div class="row">
    <div class="col-md-6 form-group">
        <label for="name">Nombre o razón social *</label>
        <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $provider->name) }}" required autofocus>
    </div>
    <div class="col-md-6 form-group">
        <label for="nit">NIT</label>
        <input type="text" class="form-control" name="nit" id="nit" value="{{ old('nit', $provider->nit) }}" maxlength="40">
    </div>
    <div class="col-md-6 form-group">
        <label for="phone">Teléfono</label>
        <input type="text" class="form-control" name="phone" id="phone" value="{{ old('phone', $provider->phone) }}" maxlength="60">
    </div>
    <div class="col-md-6 form-group">
        <label for="email">Correo</label>
        <input type="email" class="form-control" name="email" id="email" value="{{ old('email', $provider->email) }}">
    </div>
    <div class="col-12 form-group">
        <label for="address">Dirección</label>
        <input type="text" class="form-control" name="address" id="address" value="{{ old('address', $provider->address) }}">
    </div>
</div>
