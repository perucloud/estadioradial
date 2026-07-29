@extends('layouts.admin')
@section('title','Configuración del portal')
@section('eyebrow','Apariencia y contacto')
@section('heading','Configuración del portal')
@section('content')
<form method="post" action="{{ route('admin.settings.portal.update') }}" class="form-stack">@csrf @method('PUT')
<section class="panel form-stack"><div class="panel__header"><h2>Contacto</h2></div>
<div class="form-grid"><label>Correo <input type="email" name="contact[email]" value="{{ old('contact.email',$contact['email']??'') }}"></label><label>Teléfono <input name="contact[phone]" value="{{ old('contact.phone',$contact['phone']??'') }}"></label></div>
<div class="form-grid"><label>WhatsApp <input name="contact[whatsapp]" value="{{ old('contact.whatsapp',$contact['whatsapp']??'') }}"></label><label>Dirección <input name="contact[address]" value="{{ old('contact.address',$contact['address']??'') }}"></label></div></section>
<section class="panel form-stack"><div class="panel__header"><h2>Redes sociales</h2></div><div class="form-grid">@foreach(['facebook','x','tiktok','instagram','youtube'] as $network)<label>{{ ucfirst($network) }} <input type="url" name="social[{{ $network }}]" value="{{ old('social.'.$network,$social[$network]??'') }}"></label>@endforeach</div></section>
@foreach(['article'=>'Sidebar de noticias','section'=>'Sidebar de secciones'] as $key=>$label)
@php($sidebar = $key === 'article' ? $article : $section)
<section class="panel form-stack"><div class="panel__header"><h2>{{ $label }}</h2></div>
<div class="admin-choice-grid">@foreach(['most_read'=>'Más leídas','latest'=>'Últimas noticias','advertisements'=>'Publicidad','social'=>'Redes','categories'=>'Categorías'] as $module=>$name)<label class="check-row"><input type="checkbox" name="{{ $key }}[modules][]" value="{{ $module }}" @checked(in_array($module,$sidebar['modules']??[]))><span>{{ $name }}</span></label>@endforeach</div>
<div class="form-grid"><label>Cantidad más leídas <input type="number" name="{{ $key }}[most_read_limit]" value="{{ $sidebar['most_read_limit']??5 }}" min="1" max="10"></label><label>Cantidad últimas <input type="number" name="{{ $key }}[latest_limit]" value="{{ $sidebar['latest_limit']??5 }}" min="1" max="10"></label></div>
<label class="check-row"><input type="checkbox" name="{{ $key }}[sticky]" value="1" @checked($sidebar['sticky']??true)><span>Columna fija en escritorio</span></label>
@if($key==='section')<label class="check-row"><input type="checkbox" name="section[adaptive]" value="1" @checked($section['adaptive']??true)><span>Adaptar a la altura del contenido</span></label>@endif
</section>@endforeach
<button class="button button--primary">Guardar configuración</button></form>
@endsection
