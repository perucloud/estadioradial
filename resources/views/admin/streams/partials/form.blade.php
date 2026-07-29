<form method="post" action="{{ $action }}" class="form-stack taxonomy-form">
    @csrf
    @if ($stream) @method('PUT') @endif
    <div class="form-grid">
        <label>Nombre <input name="name" value="{{ old('name', $stream?->name) }}" required maxlength="120"></label>
        <label>Tipo <select name="type"><option value="audio" @selected(old('type', $stream?->type) === 'audio')>Audio</option><option value="video" @selected(old('type', $stream?->type) === 'video')>Video</option></select></label>
    </div>
    <div class="form-grid">
        <label>Formato <select name="format">@foreach(['mp3' => 'MP3', 'aac' => 'AAC', 'hls' => 'HLS', 'youtube' => 'YouTube', 'iframe' => 'Iframe HTTPS'] as $value => $label)<option value="{{ $value }}" @selected(old('format', $stream?->format ?? 'mp3') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label>Orden <input type="number" name="sort_order" value="{{ old('sort_order', $stream?->sort_order ?? 100) }}" min="0" max="65535"></label>
    </div>
    <label>URL HTTPS <input type="url" name="url" value="{{ old('url', $stream?->url) }}" placeholder="https://proveedor.example/stream"></label>
    <label>Mensaje alternativo <input name="fallback_message" value="{{ old('fallback_message', $stream?->fallback_message) }}" maxlength="255" placeholder="Señal temporalmente no disponible"></label>
    <label>Portada <select name="media_id"><option value="">Portada predeterminada</option>@foreach($mediaItems as $media)<option value="{{ $media->id }}" @selected((int) old('media_id', $stream?->media_id) === $media->id)>{{ $media->original_name }}</option>@endforeach</select></label>
    <div class="admin-choice-grid">
        <label class="check-row"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $stream?->is_active ?? false))><span>Señal activa</span></label>
        <label class="check-row"><input type="hidden" name="is_primary" value="0"><input type="checkbox" name="is_primary" value="1" @checked(old('is_primary', $stream?->is_primary ?? false))><span>Señal principal de este tipo</span></label>
    </div>
    <button class="button button--primary" type="submit">{{ $stream ? 'Guardar señal' : 'Crear señal' }}</button>
</form>
