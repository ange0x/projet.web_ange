@foreach ($donnees as $champ => $valeur)
    <input type="hidden" name="{{ $champ }}" value="{{ $valeur }}">
@endforeach
