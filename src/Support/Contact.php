<?php

namespace Vipertecpro\DataRightsDesk\Support;

/**
 * s.8(9), Rule 9: business contact of the Data Protection Officer, or of a
 * person who can answer questions about the processing. Shown on the rights
 * page, in every notice and in every reply to a request.
 */
final class Contact
{
    /** @return array{business: string|null, website: string|null, name: string|null, role: string|null, email: string|null, phone: string|null, address: string|null} */
    public static function details(): array
    {
        $value = fn (string $key): ?string => filled(config("data-rights.{$key}")) ? (string) config("data-rights.{$key}") : null;

        return [
            'business' => $value('business.name'),
            'website' => $value('business.website'),
            'name' => $value('contact.name'),
            'role' => $value('contact.role'),
            'email' => $value('contact.email'),
            'phone' => $value('contact.phone'),
            'address' => $value('contact.address'),
        ];
    }

    /** A person can be reached by email or phone. */
    public static function isComplete(): bool
    {
        $details = self::details();

        return $details['email'] !== null || $details['phone'] !== null;
    }

    public static function businessName(): string
    {
        return self::details()['business'] ?? (string) config('app.name', 'This app');
    }

    /**
     * Lines of the contact block in one language.
     *
     * @return list<string>
     */
    public static function lines(?string $locale = null): array
    {
        $details = self::details();
        $lines = [];

        $who = trim(implode(', ', array_filter([$details['name'], $details['role']])));

        if ($who !== '') {
            $lines[] = $who;
        }

        $lines[] = (string) __('data-rights::notice.contact_business', ['business' => self::businessName()], $locale);

        foreach (['email', 'phone', 'address'] as $field) {
            if ($details[$field] !== null) {
                $lines[] = (string) __("data-rights::notice.contact_{$field}", ['value' => $details[$field]], $locale);
            }
        }

        if (! self::isComplete()) {
            $lines[] = (string) __('data-rights::notice.contact_missing', [], $locale);
        }

        return $lines;
    }

    public static function markdown(?string $locale = null): string
    {
        return implode("  \n", self::lines($locale));
    }
}
