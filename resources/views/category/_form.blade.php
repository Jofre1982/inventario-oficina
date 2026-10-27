<div class="form-group">
    <label for="name">Nombre *</label>
    <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" class="form-control" maxlength="100" required autofocus>
</div>
<div class="form-group">
    <label for="description">Descripción</label>
    <textarea class="form-control" name="description" id="description" rows="3" maxlength="255">{{ old('description', $category->description) }}</textarea>
</div>
