<?php

namespace Vipertecpro\DataRightsDesk\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as Notifier;

/**
 * Helpers for the Data Principal model (config('data-rights.subject_model')).
 */
final class Subjects
{
    /** @return class-string<Model> */
    public static function modelClass(): string
    {
        /** @var class-string<Model> $class */
        $class = (string) config('data-rights.subject_model', 'App\\Models\\User');

        return $class;
    }

    public static function emailColumn(): string
    {
        return (string) config('data-rights.subject_email_column', 'email');
    }

    public static function findByEmail(string $email): ?Model
    {
        $class = self::modelClass();

        if (! class_exists($class)) {
            return null;
        }

        return $class::query()->where(self::emailColumn(), trim($email))->first();
    }

    public static function find(string $type, int|string $id): ?Model
    {
        $class = Relation::getMorphedModel($type) ?? $type;

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return null;
        }

        return $class::query()->find($id);
    }

    public static function email(Model $subject): ?string
    {
        $email = $subject->getAttribute(self::emailColumn());

        return is_string($email) && $email !== '' ? $email : null;
    }

    /** Shown only on the dashboard. */
    public static function label(Model $subject): string
    {
        if (method_exists($subject, 'dataRightsLabel')) {
            return (string) $subject->dataRightsLabel();
        }

        return self::email($subject) ?? (string) $subject->getKey();
    }

    public static function locale(Model $subject): ?string
    {
        if (method_exists($subject, 'dataRightsLocale')) {
            $locale = $subject->dataRightsLocale();

            return is_string($locale) && $locale !== '' ? $locale : null;
        }

        return null;
    }

    /**
     * Sends a notification to the person: through the model when it is
     * Notifiable, else by mail to the email column. False when there is no route.
     */
    public static function notify(Model $subject, Notification $notification): bool
    {
        if (method_exists($subject, 'notify')) {
            $subject->notify($notification);

            return true;
        }

        $email = self::email($subject);

        if ($email === null) {
            return false;
        }

        Notifier::route('mail', $email)->notify($notification);

        return true;
    }
}
