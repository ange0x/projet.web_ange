<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MotDePasseOublieController extends Controller
{
    public const MESSAGE_ENVOI = 'Si cette adresse correspond à un compte actif, un e-mail contenant un lien de réinitialisation vient d’être envoyé. Le lien est valable 60 minutes.';

    public const MESSAGE_LIEN_INVALIDE = 'Ce lien est invalide ou a expiré. Faites une nouvelle demande.';

    public function demande(): View
    {
        return view('auth.mot-de-passe-oublie');
    }

    /**
     * R17 : le même message quel que soit le résultat, pour ne pas révéler quels comptes existent.
     */
    public function envoyer(Request $request): RedirectResponse
    {
        $this->normaliserEmail($request);

        $donnees = $request->validate(
            ['email' => ['required', 'email', 'max:255']],
            [
                'email.required' => 'Saisissez votre adresse e-mail.',
                'email.email' => 'Saisissez une adresse e-mail valide.',
                'email.max' => 'L’adresse e-mail est trop longue.',
            ],
        );

        // Seuls les comptes actifs et non archivés reçoivent un lien.
        Password::broker()->sendResetLink([
            'email' => $donnees['email'],
            'actif' => true,
        ]);

        return redirect()
            ->route('mot-de-passe.demande')
            ->with('statut', self::MESSAGE_ENVOI);
    }

    public function formulaire(Request $request, string $token): View
    {
        $email = $request->query('email');

        return view('auth.reinitialisation', [
            'token' => $token,
            'email' => is_string($email) ? $email : '',
        ]);
    }

    /**
     * R20 et R21 : valide, change le mot de passe, débloque, ferme les sessions et journalise.
     */
    public function reinitialiser(Request $request): RedirectResponse
    {
        $this->normaliserEmail($request);

        $donnees = $request->validate(
            [
                'token' => ['required', 'string'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            ],
            [
                'token.required' => self::MESSAGE_LIEN_INVALIDE,
                'email.required' => 'L’adresse e-mail est manquante : ouvrez de nouveau le lien reçu.',
                'email.email' => self::MESSAGE_LIEN_INVALIDE,
                'password.required' => 'Saisissez un nouveau mot de passe.',
                'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
                'password.max' => 'Le mot de passe est trop long.',
                'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            ],
        );

        $statut = Password::broker()->reset(
            [
                'email' => $donnees['email'],
                'actif' => true,
                'token' => $donnees['token'],
                'password' => $donnees['password'],
                'password_confirmation' => $request->input('password_confirmation'),
            ],
            function (User $user, string $motDePasse): void {
                $user->forceFill([
                    'password' => $motDePasse,
                    'remember_token' => Str::random(60),
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                ])->save();

                // R21 : toutes les sessions ouvertes de ce compte sont fermées.
                DB::table('sessions')->where('user_id', $user->id)->delete();

                activity('comptes')
                    ->causedBy($user)
                    ->performedOn($user)
                    ->event('password_reset')
                    ->log('Mot de passe réinitialisé');

                event(new PasswordReset($user));
            },
        );

        if ($statut !== Password::PASSWORD_RESET) {
            // Le même message pour un jeton faux, expiré, déjà utilisé ou une adresse inconnue.
            return back()->withErrors(['email' => self::MESSAGE_LIEN_INVALIDE]);
        }

        return redirect()
            ->route('connexion')
            ->with('statut', 'Votre mot de passe a été modifié. Vous pouvez vous connecter.');
    }

    private function normaliserEmail(Request $request): void
    {
        $email = $request->input('email');

        if (is_string($email)) {
            $request->merge(['email' => mb_strtolower(trim($email))]);
        }
    }
}
