@php
    $edition = isset($user);
    $actif = $errors->any() ? (bool) old('actif') : ($edition ? $user->actif : true);
    $roleCourant = old('role', $edition ? $user->role->value : 'secretariat');
@endphp

@if ($errors->any())
    <div class="erreurs" role="alert">
        <h2>Veuillez corriger les erreurs suivantes</h2>
        <ul>
            @foreach ($errors->all() as $erreur)
                <li>{{ $erreur }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="post" action="{{ $action }}" novalidate>
    @csrf
    @if ($edition)
        @method('PUT')
    @endif

    <div class="champ">
        <label for="name">Nom complet</label>
        <input id="name" name="name" type="text" maxlength="100" required
               autocomplete="off"
               value="{{ old('name', $edition ? $user->name : '') }}"
               @error('name') aria-invalid="true" aria-describedby="erreur-name" @enderror>
        @error('name') <p class="erreur-champ" id="erreur-name">{{ $message }}</p> @enderror
    </div>

    <div class="champ">
        <label for="identifiant">Identifiant</label>
        <input id="identifiant" name="identifiant" type="text" maxlength="100" required
               autocomplete="off"
               value="{{ old('identifiant', $edition ? $user->identifiant : '') }}"
               @error('identifiant') aria-invalid="true" aria-describedby="erreur-identifiant" @enderror>
        @error('identifiant') <p class="erreur-champ" id="erreur-identifiant">{{ $message }}</p> @enderror
    </div>

    <div class="champ">
    <label for="email">E-mail (facultatif)</label>
    <input id="email" name="email" type="email" maxlength="255" autocomplete="off"
           value="{{ old('email', $edition ? $user->email : '') }}"
           aria-describedby="aide-email @error('email') erreur-email @enderror"
           @error('email') aria-invalid="true" @enderror>
    <p class="aide" id="aide-email">Sert à envoyer le lien de réinitialisation du mot de passe.</p>
    @error('email') <p class="erreur-champ" id="erreur-email">{{ $message }}</p> @enderror
</div>

    <div class="champ">
        <label for="password">{{ $edition ? 'Nouveau mot de passe (laisser vide pour conserver l’actuel)' : 'Mot de passe' }}</label>
        <input id="password" name="password" type="password" minlength="8"
               autocomplete="new-password" @unless($edition) required @endunless
               aria-describedby="aide-password @error('password') erreur-password @enderror"
               @error('password') aria-invalid="true" @enderror>
        <p class="aide" id="aide-password">8 caractères au moins.</p>
        @error('password') <p class="erreur-champ" id="erreur-password">{{ $message }}</p> @enderror
    </div>

    <div class="champ">
        <label for="password_confirmation">Confirmation du mot de passe</label>
        <input id="password_confirmation" name="password_confirmation" type="password"
               autocomplete="new-password" @unless($edition) required @endunless>
    </div>

    <div class="champ">
        <label for="role">Rôle</label>
        <select id="role" name="role" required
                @error('role') aria-invalid="true" aria-describedby="erreur-role" @enderror>
            @foreach (App\Enums\Role::cases() as $role)
                <option value="{{ $role->value }}" @selected($roleCourant === $role->value)>{{ $role->label() }}</option>
            @endforeach
        </select>
        @error('role') <p class="erreur-champ" id="erreur-role">{{ $message }}</p> @enderror
    </div>

    <div class="champ champ-case">
        <input id="actif" name="actif" type="checkbox" value="1" @checked($actif)
               @error('actif') aria-invalid="true" aria-describedby="erreur-actif" @enderror>
        <label for="actif">Compte actif (peut se connecter)</label>
        @error('actif') <p class="erreur-champ" id="erreur-actif">{{ $message }}</p> @enderror
    </div>

    <div class="actions">
        <button type="submit">{{ $bouton }}</button>
        <a href="{{ route('comptes.index') }}">Annuler</a>
    </div>
</form>
