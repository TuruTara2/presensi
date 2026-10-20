<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/** Email reset password versi Bahasa Indonesia. */
class ResetPasswordNotification extends ResetPassword
{
    protected function buildMailMessage($url)
    {
        $menit = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Reset Password - '.config('app.name'))
            ->greeting('Halo!')
            ->line('Kami menerima permintaan untuk mengganti password akun presensi kamu.')
            ->action('Ganti Password', $url)
            ->line("Link ini berlaku selama {$menit} menit dan hanya bisa dipakai sekali.")
            ->line('Kalau kamu tidak merasa meminta reset password, abaikan saja email ini. Password kamu tidak berubah.')
            ->salutation('Salam, '.config('app.name'));
    }
}
