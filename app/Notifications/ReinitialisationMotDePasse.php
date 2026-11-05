<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ReinitialisationMotDePasse extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Vous avez demandé la réinitialisation du mot de passe de votre compte sur l’application de gestion des inscriptions.')
            ->action('Choisir un nouveau mot de passe', $this->resetUrl($notifiable))
            ->line('Ce lien est valable '.$minutes.' minutes et ne peut être utilisé qu’une seule fois.')
            ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez ce message : votre mot de passe ne sera pas modifié.')
            ->salutation('ESGC Verechaguine AK');
    }
}
