@php
    $erreur = $errors->has($champ);
    $courant = (string) old($champ, $defaut ?? '');
@endphp
<div class="champ">
    <label for="{{ $champ }}">{{ $label }}@if ($obligatoire ?? false) <span aria-hidden="true">*</span>@endif</label>
    <select id="{{ $champ }}" name="{{ $champ }}"
            @if ($obligatoire ?? false) required @endif
            @if ($erreur) aria-invalid="true" aria-describedby="erreur-{{ $champ }}" @endif>
        <option value="">Choisir…</option>
        @foreach ($options as $valeur => $libelle)
            <option value="{{ $valeur }}" @selected($courant === (string) $valeur)>{{ $libelle }}</option>
        @endforeach
    </select>
    @if ($erreur)
        <p class="erreur-champ" id="erreur-{{ $champ }}">{{ $errors->first($champ) }}</p>
    @endif
</div>
