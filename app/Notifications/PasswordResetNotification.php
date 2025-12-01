<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends ResetPassword
{
    /**
     * Build the mail representation of the notification.
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('Сброс пароля')
            ->greeting('Здравствуйте')
            ->line([
                'Вы получаете это электронное письмо, потому что мы получили запрос на сброс пароля для вашей учетной записи.',
               // trans('backpack::base.password_reset.line_2'),
            ])
            ->action('Сбросить', route('password.reset', [
                'action' => 'forgot',
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset()
            ]))
            ->line('Если вы не запросили сброс пароля, никаких дальнейших действий не требуется.');
    }
}
