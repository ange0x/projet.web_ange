@php
    $erreur = $errors->has($champ);
    $aideId = ! empty($aide) ? 'aide-'.$champ : '';
    $erreurId = $erreur ? 'erreur-'.$champ : '';
    $decrit = trim($aideId.' '.$erreurId);
@endphp
<div class="champ">
    <label for="{{ $champ }}">{{ $label }}@if ($obligatoire ?? false) <span aria-hidden="true">*</span>@endif</label>
    <input id="{{ $champ }}" name="{{ $champ }}" type="{{ $type ?? 'text' }}"
           value="{{ old($champ, $defaut ?? '') }}"
           autocomplete="off"
           @if (! empty($maxlength)) maxlength="{{ $maxlength }}" @endif
           @if (! empty($max)) max="{{ $max }}" @endif
           @if (! empty($mode)) inputmode="{{ $mode }}" @endif
           @if (! empty($motif)) pattern="{{ $motif }}" @endif
           @if ($obligatoire ?? false) required @endif
           @if ($decrit !== '') aria-describedby="{{ $decrit }}" @endif
           @if ($erreur) aria-invalid="true" @endif>
    @if (! empty($aide))
        <p class="aide" id="aide-{{ $champ }}">{{ $aide }}</p>
    @endif
    @if ($erreur)
        <p class="erreur-champ" id="erreur-{{ $champ }}">{{ $errors->first($champ) }}</p>
    @endif
</div>
