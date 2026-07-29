<form method="post" action="{{ $action }}" class="form-stack taxonomy-form">
@csrf @if($advertisement) @method('PUT') @endif
<div class="form-grid"><label>Nombre <input name="name" value="{{ old('name', $advertisement?->name) }}" required></label><label>Ubicación <select name="placement">@foreach($placements as $value=>$label)<option value="{{ $value }}" @selected(old('placement',$advertisement?->placement)===$value)>{{ $label }}</option>@endforeach</select></label></div>
<label>Imagen de Media <select name="media_id" required><option value="">Seleccionar</option>@foreach($mediaItems as $media)<option value="{{ $media->id }}" @selected((int)old('media_id',$advertisement?->media_id)===$media->id)>{{ $media->original_name }}</option>@endforeach</select></label>
<label>Texto alternativo <input name="alt_text" value="{{ old('alt_text',$advertisement?->alt_text) }}"></label>
<label>URL de destino <input type="url" name="destination_url" value="{{ old('destination_url',$advertisement?->destination_url) }}"></label>
<div class="form-grid"><label>Inicio <input type="datetime-local" name="starts_at" value="{{ old('starts_at',$advertisement?->starts_at?->format('Y-m-d\\TH:i')) }}"></label><label>Fin <input type="datetime-local" name="ends_at" value="{{ old('ends_at',$advertisement?->ends_at?->format('Y-m-d\\TH:i')) }}"></label></div>
<label>Orden <input type="number" name="sort_order" value="{{ old('sort_order',$advertisement?->sort_order ?? 100) }}"></label>
<div class="admin-choice-grid"><label class="check-row"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$advertisement?->is_active ?? true))><span>Activa</span></label><label class="check-row"><input type="hidden" name="open_in_new_tab" value="0"><input type="checkbox" name="open_in_new_tab" value="1" @checked(old('open_in_new_tab',$advertisement?->open_in_new_tab ?? true))><span>Abrir en nueva pestaña</span></label></div>
<button class="button button--primary">{{ $advertisement ? 'Guardar' : 'Crear publicidad' }}</button>
</form>
